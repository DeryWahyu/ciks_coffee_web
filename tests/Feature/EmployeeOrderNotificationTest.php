<?php

namespace Tests\Feature;

use App\Events\MobileOrderReceived;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeOrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_an_active_employee_can_read_notification_data(): void
    {
        $url = route('karyawan.orders.notifications');
        $this->getJson($url)->assertUnauthorized();
        foreach (['pemilik', 'pengguna'] as $role) {
            $this->actingAs($this->user($role), 'web')->getJson($url)->assertForbidden();
        }
        $employee = $this->user('karyawan');
        $this->actingAs($employee, 'web')->getJson($url)->assertOk();
        $employee->update(['is_active' => false]);
        $this->getJson($url)->assertForbidden();
    }

    public function test_feed_returns_only_mobile_qris_orders_still_waiting_for_verification(): void
    {
        $customer = $this->user('pengguna');
        $employee = $this->user('karyawan');
        $waiting = $this->order($customer);
        foreach (['antrian_baru', 'sedang_dibuat', 'selesai', 'diambil'] as $status) {
            $this->order($customer, ['status' => $status]);
        }
        $this->order($employee);
        $this->order($customer, ['payment_method' => 'cash']);
        $response = $this->actingAs($employee, 'web')->getJson(route('karyawan.orders.notifications'))
            ->assertOk()->assertJsonCount(1, 'orders')->assertJsonPath('orders.0.id', $waiting->id)
            ->assertJsonPath('orders.0.order_number', $waiting->order_number)->assertJsonPath('next_cursor', null);
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertArrayNotHasKey('payment_proof', $response->json('orders.0'));
    }

    public function test_more_than_fifty_waiting_orders_have_a_following_page_without_duplicates(): void
    {
        $customer = $this->user('pengguna');
        for ($index = 0; $index < 51; $index++) {
            $this->order($customer);
        }
        $this->actingAs($this->user('karyawan'), 'web');
        $first = $this->getJson(route('karyawan.orders.notifications'))->assertOk()->assertJsonCount(50, 'orders');
        $second = $this->getJson(route('karyawan.orders.notifications', ['after_id' => $first->json('next_cursor')]))
            ->assertOk()->assertJsonCount(1, 'orders')->assertJsonPath('next_cursor', null);
        $this->assertNotContains($second->json('orders.0.id'), array_column($first->json('orders'), 'id'));
    }

    public function test_a_real_mobile_checkout_becomes_discoverable_without_websocket_delivery(): void
    {
        Storage::fake('local');
        Event::fake([MobileOrderReceived::class]);
        [$product] = $this->product();
        Sanctum::actingAs($this->user('pengguna'));
        $response = $this->postJson('/api/orders', ['payment_method' => 'qris',
            'payment_proof' => UploadedFile::fake()->image('proof.png'),
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();
        Event::assertDispatchedTimes(MobileOrderReceived::class, 1);
        $this->actingAs($this->user('karyawan'), 'web')->getJson(route('karyawan.orders.notifications'))
            ->assertOk()->assertJsonPath('orders.0.id', $response->json('order.id'));
    }

    public function test_notification_transport_failure_does_not_hide_a_committed_order_from_the_feed(): void
    {
        Storage::fake('local');
        Event::listen(MobileOrderReceived::class, static fn () => throw new \RuntimeException('Test broadcast unavailable'));
        [$product] = $this->product();
        Sanctum::actingAs($this->user('pengguna'));
        $response = $this->postJson('/api/orders', ['payment_method' => 'qris',
            'payment_proof' => UploadedFile::fake()->image('proof.png'),
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();
        $this->actingAs($this->user('karyawan'), 'web')->getJson(route('karyawan.orders.notifications'))
            ->assertOk()->assertJsonPath('orders.0.id', $response->json('order.id'));
    }

    public function test_verified_orders_stop_appearing_and_cursors_are_validated(): void
    {
        $order = $this->order($this->user('pengguna'));
        $this->actingAs($this->user('karyawan'), 'web')->getJson(route('karyawan.orders.notifications'))->assertJsonCount(1, 'orders');
        $order->updateQuietly(['status' => 'antrian_baru']);
        $this->getJson(route('karyawan.orders.notifications'))->assertJsonCount(0, 'orders');
        foreach ([-1, 'invalid'] as $cursor) {
            $this->getJson(route('karyawan.orders.notifications', ['after_id' => $cursor]))->assertUnprocessable();
        }
    }

    public function test_private_channel_rejects_owners_customers_and_inactive_employees(): void
    {
        config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret', 'broadcasting.connections.reverb.app_id' => 'test-app']);
        require base_path('routes/channels.php');
        $payload = ['socket_id' => '1234.1234', 'channel_name' => 'private-karyawan.orders'];
        foreach (['pemilik', 'pengguna'] as $role) {
            $this->actingAs($this->user($role), 'web')->postJson('/broadcasting/auth', $payload)->assertForbidden();
        }
        $employee = $this->user('karyawan');
        $this->actingAs($employee, 'web')->postJson('/broadcasting/auth', $payload)->assertOk();
        $employee->update(['is_active' => false]);
        $this->actingAs($employee, 'web')->postJson('/broadcasting/auth', $payload)->assertForbidden();
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function order(User $user, array $values = []): Order
    {
        return Order::create($values + ['user_id' => $user->id, 'customer_name' => $user->name,
            'order_number' => 'CK-NOTIFY-'.Str::uuid(), 'payment_method' => 'qris',
            'status' => 'menunggu_verifikasi', 'total' => 15000]);
    }

    private function product(): array
    {
        $category = Category::create(['name' => 'Snack', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Produk notifikasi', 'price' => 15000, 'is_active' => true]);
        $ingredient = Ingredient::create(['nama_bahan' => 'Bahan notifikasi', 'satuan' => 'gram', 'stok' => 10]);
        $product->ingredients()->attach($ingredient->id, ['quantity' => 1]);

        return [$product, $ingredient];
    }
}
