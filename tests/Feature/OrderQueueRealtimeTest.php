<?php

namespace Tests\Feature;

use App\Events\OrderQueueUpdated;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderQueueRealtimeTest extends TestCase
{
    use DatabaseMigrations;

    public function test_mobile_pickup_publishes_one_queue_change_after_commit_and_retry_is_idempotent(): void
    {
        $customer = $this->user('pengguna');
        $order = $this->order($customer);
        Event::fake([OrderQueueUpdated::class]);
        Sanctum::actingAs($customer);
        $this->postJson('/api/orders/'.$order->id.'/pickup')->assertOk();
        $this->postJson('/api/orders/'.$order->id.'/pickup')->assertOk();
        Event::assertDispatchedTimes(OrderQueueUpdated::class, 1);
        Event::assertDispatched(OrderQueueUpdated::class, fn ($event) => $event->orderId === $order->id && $event->status === 'diambil');
    }

    public function test_a_rolled_back_queue_change_is_never_broadcast(): void
    {
        $order = $this->order($this->user('pengguna'));
        Event::fake([OrderQueueUpdated::class]);
        try {
            DB::transaction(function () use ($order) {
                $order->update(['status' => 'diambil']);
                Event::assertNotDispatched(OrderQueueUpdated::class);
                throw new \RuntimeException('Rollback fixture');
            });
        } catch (\RuntimeException) {
        }
        Event::assertNotDispatched(OrderQueueUpdated::class);
        $this->assertSame('selesai', $order->fresh()->status);
    }

    public function test_broadcast_outage_does_not_fail_a_committed_mobile_pickup(): void
    {
        $customer = $this->user('pengguna');
        $order = $this->order($customer);
        Event::listen(OrderQueueUpdated::class, static fn () => throw new \RuntimeException('Broadcast unavailable'));
        Sanctum::actingAs($customer);
        $this->postJson('/api/orders/'.$order->id.'/pickup')->assertOk();
        $this->assertSame('diambil', $order->fresh()->status);
    }

    public function test_snapshot_updates_cards_and_counts_after_pickup_and_has_no_colored_left_border(): void
    {
        $customer = $this->user('pengguna');
        $order = $this->order($customer);
        $this->actingAs($this->user('karyawan'), 'web');
        $url = route('karyawan.orders.snapshot');
        $first = $this->getJson($url)->assertOk();
        $this->assertStringContainsString('data-order-id="'.$order->id.'"', $first->json('html'));
        $this->assertStringNotContainsString('border-l-4', $first->json('html'));
        $this->getJson($url.'?revision='.$first->json('revision'))->assertNoContent();
        Sanctum::actingAs($customer);
        $this->postJson('/api/orders/'.$order->id.'/pickup')->assertOk();
        $this->actingAs($this->user('karyawan'), 'web');
        $next = $this->getJson($url.'?revision='.$first->json('revision'))->assertOk();
        $this->assertStringNotContainsString('data-order-id="'.$order->id.'"', $next->json('html'));
        $this->assertStringContainsString('Siap Diambil (0)', $next->json('html'));
        $this->assertNotSame($first->json('revision'), $next->json('revision'));
        $this->assertStringContainsString('no-store', $next->headers->get('Cache-Control'));
    }

    public function test_a_disappearing_last_page_is_clamped_while_preserving_the_status_filter(): void
    {
        Event::fake([OrderQueueUpdated::class]);
        $customer = $this->user('pengguna');
        $oldest = $this->order($customer);
        for ($index = 0; $index < 15; $index++) {
            $this->order($customer);
        }
        $this->actingAs($this->user('karyawan'), 'web');
        $url = route('karyawan.orders.snapshot', ['status' => 'selesai', 'page' => 2]);
        $first = $this->getJson($url)->assertOk();
        $this->assertSame(1, substr_count($first->json('html'), 'data-order-id='));
        $oldest->updateQuietly(['status' => 'diambil']);
        $next = $this->getJson($url)->assertOk();
        $this->assertSame(15, substr_count($next->json('html'), 'data-order-id='));
        $this->assertStringContainsString('status=selesai', $next->json('url'));
        $this->assertStringNotContainsString('page=2', $next->json('url'));
        $this->assertStringNotContainsString('href="'.route('karyawan.orders.snapshot'), $next->json('html'));
    }

    public function test_only_active_employees_can_request_a_queue_snapshot(): void
    {
        $url = route('karyawan.orders.snapshot');
        $this->getJson($url)->assertUnauthorized();
        foreach (['pemilik', 'pengguna'] as $role) {
            $this->actingAs($this->user($role), 'web')->getJson($url)->assertForbidden();
        }
        $employee = $this->user('karyawan');
        $this->actingAs($employee, 'web')->getJson($url)->assertOk();
        $employee->update(['is_active' => false]);
        $this->getJson($url)->assertForbidden();
    }

    public function test_creation_and_cashier_assignment_notify_the_shared_queue(): void
    {
        Event::fake([OrderQueueUpdated::class]);
        $order = $this->order($this->user('pengguna'));
        Event::assertDispatchedTimes(OrderQueueUpdated::class, 1);
        Event::fake([OrderQueueUpdated::class]);
        $order->update(['cashier_id' => $this->user('karyawan')->id]);
        Event::assertDispatchedTimes(OrderQueueUpdated::class, 1);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function order(User $user): Order
    {
        return Order::create(['user_id' => $user->id, 'customer_name' => $user->name,
            'order_number' => 'CK-QUEUE-'.Str::uuid(), 'payment_method' => 'qris',
            'status' => 'selesai', 'total' => 15000, 'paid_at' => now()]);
    }
}
