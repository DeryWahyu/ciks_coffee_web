<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobilePickupTransactionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_web_histories_and_their_stats_hide_mobile_orders_until_pickup(): void
    {
        $owner = User::factory()->create(['role' => 'pemilik', 'is_active' => true]);
        $employee = User::factory()->create(['role' => 'karyawan', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'pengguna', 'is_active' => true]);
        foreach (['menunggu_verifikasi', 'antrian_baru', 'sedang_dibuat', 'selesai'] as $status) {
            $waiting[] = $this->order($customer, $employee, $status, 99000);
        }
        $pickedUp = $this->order($customer, $employee, 'diambil', 25000);
        $pos = $this->order($employee, $employee, 'antrian_baru', 15000);

        foreach ([[$owner, 'pemilik.reports.transactions'], [$employee, 'karyawan.orders.history']] as [$user, $route]) {
            $response = $this->actingAs($user, 'web')->get(route($route))->assertOk();
            $response->assertSeeText($pickedUp->order_number)->assertSeeText($pos->order_number)
                ->assertViewHas('stats', fn ($stats) => $stats['total_transactions'] === 2
                    && (float) $stats['total_revenue'] === 40000.0
                    && $stats['today_count'] === 2);
            foreach ($waiting as $order) {
                $response->assertDontSeeText($order->order_number);
            }
            $this->get(route($route, ['search' => $waiting[3]->order_number, 'status' => 'selesai']))
                ->assertOk()->assertViewHas('orders', fn ($orders) => $orders->total() === 0)
                ->assertViewHas('stats', fn ($stats) => $stats['total_transactions'] === 0);
        }
        $this->actingAs($owner, 'web')->get(route('pemilik.reports.transactions.receipt', $waiting[3]))->assertNotFound();
        $this->get(route('pemilik.reports.transactions.receipt', $pickedUp))->assertOk();
    }

    public function test_customer_confirmation_moves_ready_order_to_history_without_changing_stock_or_payment(): void
    {
        $owner = User::factory()->create(['role' => 'pemilik', 'is_active' => true]);
        $employee = User::factory()->create(['role' => 'karyawan', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'pengguna', 'is_active' => true]);
        $order = $this->order($customer, $employee, 'selesai', 24000);
        $ingredient = Ingredient::create(['nama_bahan' => 'Kopi', 'satuan' => 'gram', 'stok' => 150]);
        $paidAt = $order->paid_at;
        Sanctum::actingAs($customer);
        $this->getJson('/api/orders/active')->assertJsonCount(1, 'data');
        $this->getJson('/api/orders/history')->assertJsonCount(0, 'data');
        $this->postJson('/api/orders/'.$order->id.'/pickup')->assertOk()->assertJsonPath('success', true);
        $updatedAt = $order->fresh()->updated_at;
        $this->travel(2)->seconds();
        $this->postJson('/api/orders/'.$order->id.'/pickup')->assertOk();
        $this->assertTrue($order->fresh()->updated_at->equalTo($updatedAt));
        $this->getJson('/api/orders/active')->assertJsonCount(0, 'data');
        $this->getJson('/api/orders/history')->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'diambil');
        $this->assertSame('150.00', $ingredient->fresh()->stok);
        $this->assertTrue($order->fresh()->paid_at->equalTo($paidAt));
        foreach ([[$owner, 'pemilik.reports.transactions'], [$employee, 'karyawan.orders.history']] as [$user, $route]) {
            $this->actingAs($user, 'web')->get(route($route))->assertOk()->assertSeeText($order->order_number);
        }
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_pickup_cannot_be_forged_by_another_customer_employee_or_an_early_order(): void
    {
        $employee = User::factory()->create(['role' => 'karyawan', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'pengguna', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'pengguna', 'is_active' => true]);
        $ready = $this->order($customer, $employee, 'selesai');
        $pos = $this->order($employee, $employee, 'selesai');
        Sanctum::actingAs($other);
        $this->postJson('/api/orders/'.$ready->id.'/pickup')->assertForbidden();
        Sanctum::actingAs($employee);
        $this->postJson('/api/orders/'.$pos->id.'/pickup')->assertForbidden();
        Sanctum::actingAs($customer);
        foreach (['menunggu_verifikasi', 'antrian_baru', 'sedang_dibuat'] as $status) {
            $early = $this->order($customer, $employee, $status);
            $this->postJson('/api/orders/'.$early->id.'/pickup')->assertUnprocessable();
            $this->assertSame($status, $early->fresh()->status);
        }
        $this->assertSame('selesai', $ready->fresh()->status);
        $this->assertSame('selesai', $pos->fresh()->status);
    }

    public function test_exports_revenue_and_employee_income_use_the_same_pickup_rule(): void
    {
        // The production analytics query uses MariaDB's HOUR() function.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('HOUR', static fn (string $value): int => (int) date('G', strtotime($value)), 1);
        }
        $owner = User::factory()->create(['role' => 'pemilik', 'is_active' => true]);
        $employee = User::factory()->create(['role' => 'karyawan', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'pengguna', 'is_active' => true]);
        $waiting = $this->order($customer, $employee, 'selesai', 99000);
        $pickedUp = $this->order($customer, $employee, 'diambil', 25000);
        $pos = $this->order($employee, $employee, 'selesai', 15000);
        $category = Category::create(['name' => 'Menu uji', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Produk transaksi', 'price' => 15000, 'is_active' => true]);
        foreach ([$waiting, $pickedUp, $pos] as $order) {
            $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 1, 'price' => $order->total, 'subtotal' => $order->total]);
        }
        $csv = $this->actingAs($owner, 'web')->get(route('pemilik.exports.csv'))->assertOk()->streamedContent();
        $this->assertStringNotContainsString($waiting->order_number, $csv);
        $this->assertStringContainsString($pickedUp->order_number, $csv);
        $this->assertStringContainsString($pos->order_number, $csv);
        $this->get(route('pemilik.dashboard'))->assertOk()
            ->assertViewHas('todayRevenue', fn ($revenue) => (float) $revenue === 40000.0);
        $this->get(route('pemilik.reports.sales'))->assertOk()
            ->assertViewHas('totalRevenue', fn ($revenue) => (float) $revenue === 40000.0)
            ->assertViewHas('topProducts', fn ($products) => (int) $products->sum('total_quantity') === 2);
        $this->get(route('pemilik.analytics.index'))->assertOk()
            ->assertViewHas('kpi', fn ($kpi) => (float) $kpi['revenue']['current'] === 40000.0);
        $this->get(route('pemilik.performance.employees'))->assertOk()
            ->assertViewHas('grandTotal', fn ($revenue) => (float) $revenue === 40000.0);
        $this->get(route('pemilik.performance.employee-detail', $employee))->assertOk()
            ->assertViewHas('summary', fn ($summary) => (float) $summary->total_revenue === 40000.0);
        $this->get(route('pemilik.performance.products'))->assertOk();
        $this->actingAs($employee, 'web')->get(route('karyawan.income.index'))->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['total_transactions'] === 2 && (float) $stats['total_revenue'] === 40000.0);
        $this->get(route('karyawan.orders.index'))->assertOk()->assertSeeText($waiting->order_number)->assertDontSeeText($pickedUp->order_number);
    }

    private function order(User $creator, User $employee, string $status, int $total = 15000): Order
    {
        return Order::create([
            'order_number' => 'CK-PICKUP-'.str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT),
            'customer_name' => $creator->name, 'user_id' => $creator->id, 'cashier_id' => $employee->id,
            'payment_method' => 'qris', 'total' => $total, 'status' => $status, 'paid_at' => now(),
        ]);
    }
}
