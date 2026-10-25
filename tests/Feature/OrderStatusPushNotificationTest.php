<?php

namespace Tests\Feature;

use App\Exceptions\FcmDeliveryException;
use App\Jobs\SendOrderStatusPushNotification;
use App\Models\DeviceToken;
use App\Models\Order;
use App\Models\User;
use App\Services\FirebaseCloudMessaging;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderStatusPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_change_queues_a_push_job_for_each_registered_device(): void
    {
        Queue::fake();
        [$order, $deviceToken] = $this->makeOrderAndDevice('antrian_baru');

        DB::transaction(fn () => $order->update(['status' => 'sedang_dibuat']));

        Queue::assertPushed(
            SendOrderStatusPushNotification::class,
            fn (SendOrderStatusPushNotification $job): bool => $job->orderId === $order->id
                && $job->deviceTokenId === $deviceToken->id
                && $job->previousStatus === 'antrian_baru'
                && $job->status === 'sedang_dibuat',
        );
    }

    public function test_job_sends_only_order_status_metadata_to_the_customers_device(): void
    {
        [$order, $deviceToken] = $this->makeOrderAndDevice('sedang_dibuat');

        $messaging = $this->createMock(FirebaseCloudMessaging::class);
        $messaging
            ->expects($this->once())
            ->method('sendToDevice')
            ->with(
                $deviceToken->token,
                'Pesanan sedang dibuat',
                "Barista sedang menyiapkan pesanan {$order->order_number}.",
                $this->callback(fn (array $data): bool => $data['type'] === 'order_status'
                    && $data['order_id'] === (string) $order->id
                    && $data['status'] === 'sedang_dibuat'
                    && ! array_key_exists('customer_name', $data)
                ),
                $order->id,
            );

        (new SendOrderStatusPushNotification(
            $order->id,
            $deviceToken->id,
            'antrian_baru',
            'sedang_dibuat',
        ))->handle($messaging);
    }

    public function test_job_removes_a_token_rejected_as_unregistered_by_firebase(): void
    {
        [$order, $deviceToken] = $this->makeOrderAndDevice('selesai');

        $messaging = $this->createMock(FirebaseCloudMessaging::class);
        $messaging
            ->expects($this->once())
            ->method('sendToDevice')
            ->willThrowException(new FcmDeliveryException(
                'Token tidak lagi terdaftar.',
                true,
            ));

        (new SendOrderStatusPushNotification(
            $order->id,
            $deviceToken->id,
            'sedang_dibuat',
            'selesai',
        ))->handle($messaging);

        $this->assertModelMissing($deviceToken);
    }

    public function test_rolled_back_status_changes_do_not_queue_notifications(): void
    {
        Queue::fake();
        [$order] = $this->makeOrderAndDevice('antrian_baru');
        try {
            DB::transaction(function () use ($order) {
                $order->update(['status' => 'sedang_dibuat']);
                throw new \RuntimeException('Rollback audit');
            });
        } catch (\RuntimeException) {
        }
        Queue::assertNothingPushed();
        $this->assertSame('antrian_baru', $order->fresh()->status);
    }

    public function test_stale_ready_notifications_are_not_sent_after_customer_pickup(): void
    {
        [$order, $deviceToken] = $this->makeOrderAndDevice('selesai');
        $order->updateQuietly(['status' => 'diambil']);
        $messaging = $this->createMock(FirebaseCloudMessaging::class);
        $messaging->expects($this->never())->method('sendToDevice');
        (new SendOrderStatusPushNotification($order->id, $deviceToken->id, 'sedang_dibuat', 'selesai'))->handle($messaging);
    }

    public function test_push_queue_outage_does_not_fail_a_committed_cashier_status_update(): void
    {
        [$order] = $this->makeOrderAndDevice('antrian_baru');
        $this->mock(Dispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->once()->andThrow(new \RuntimeException('Audit: queue unavailable'));
        });
        $employee = User::factory()->create(['role' => 'karyawan', 'is_active' => true]);
        $this->actingAs($employee)->patch(route('karyawan.orders.update-status', $order), ['status' => 'sedang_dibuat'])
            ->assertSessionHas('success');
        $this->assertSame('sedang_dibuat', $order->fresh()->status);
    }

    /**
     * @return array{Order, DeviceToken}
     */
    private function makeOrderAndDevice(string $status): array
    {
        $user = User::factory()->create([
            'role' => User::ROLE_PENGGUNA,
            'is_active' => true,
        ]);
        $deviceToken = DeviceToken::query()->create([
            'user_id' => $user->id,
            'token' => 'test-fcm-token',
            'token_hash' => DeviceToken::hash('test-fcm-token'),
            'platform' => 'android',
            'last_seen_at' => now(),
        ]);
        $order = Order::query()->create([
            'order_number' => 'CK-20260728-0001',
            'customer_name' => $user->name,
            'user_id' => $user->id,
            'payment_method' => 'cash',
            'total' => 25000,
            'cash_received' => 25000,
            'change_amount' => 0,
            'status' => $status,
            'paid_at' => now(),
        ]);

        return [$order, $deviceToken];
    }
}
