<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosConsistencyAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_categories_are_hidden_and_cannot_be_sold_on_pos_or_mobile(): void
    {
        [$product, $ingredient] = $this->product();
        $product->category->update(['is_active' => false]);
        $employee = $this->staff('karyawan');
        $this->actingAs($employee)->get(route('karyawan.pos.index'))->assertOk()
            ->assertViewHas('products', fn ($products) => $products->isEmpty());
        $this->getJson(route('karyawan.pos.detail', $product))->assertNotFound();
        $this->postJson(route('karyawan.pos.checkout'), $this->payload($product))
            ->assertUnprocessable();
        Sanctum::actingAs($this->staff('pengguna'));
        $this->getJson('/api/products')->assertOk()->assertJsonCount(0, 'data');
        $this->postJson('/api/orders', $this->payload($product))->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('10.00', $ingredient->fresh()->stok);
    }

    public function test_renaming_coffee_category_preserves_its_recipe_identity(): void
    {
        [$product] = $this->product('coffee');
        $owner = $this->staff('pemilik');
        $this->actingAs($owner)->put(route('pemilik.categories.update', $product->category), ['name' => 'Kopi Spesial'])
            ->assertSessionHasNoErrors();
        $this->assertSame('coffee', $product->category->fresh()->slug);
        $this->actingAs($this->staff('karyawan'))
            ->postJson(route('karyawan.pos.checkout'), $this->payload($product, ['variant' => 'base']))
            ->assertOk()->assertJsonPath('order.items.0.product_name', 'Produk uji (Base)');
    }

    public function test_fractional_prices_and_stock_are_not_rejected_by_binary_float_rounding(): void
    {
        [$first, $ingredient] = $this->product();
        $first->update(['price' => '0.10']);
        $first->ingredients()->updateExistingPivot($ingredient->id, ['quantity' => '0.10']);
        $second = $first->replicate();
        $second->name = 'Produk kedua';
        $second->price = '0.20';
        $second->save();
        $second->ingredients()->attach($ingredient->id, ['quantity' => '0.20', 'variant' => null]);
        $ingredient->update(['stok' => '0.30']);
        $payload = $this->payload($first);
        $payload['cash_received'] = '0.30';
        $payload['items'][] = ['product_id' => $second->id, 'quantity' => 1];
        $this->actingAs($this->staff('karyawan'))->postJson(route('karyawan.pos.checkout'), $payload)
            ->assertOk()->assertJsonPath('order.total', '0.30')->assertJsonPath('order.change_amount', '0.00');
        $this->assertSame('0.00', $ingredient->fresh()->stok);
    }

    public function test_retry_of_the_same_pos_payment_returns_the_original_order_without_deducting_twice(): void
    {
        [$product, $ingredient] = $this->product();
        $payload = $this->payload($product);
        $payload['idempotency_key'] = (string) Str::uuid();
        $this->actingAs($this->staff('karyawan'));
        $first = $this->postJson(route('karyawan.pos.checkout'), $payload)->assertOk();
        // The original checkout exhausted the only portion. A replay still succeeds.
        $ingredient->update(['stok' => 0]);
        $second = $this->postJson(route('karyawan.pos.checkout'), $payload)->assertOk();
        $this->assertSame($first->json('order.id'), $second->json('order.id'));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertSame('0.00', $ingredient->fresh()->stok);
        $payload['customer_name'] = 'Pesanan berbeda';
        $this->postJson(route('karyawan.pos.checkout'), $payload)->assertUnprocessable();
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_price_changes_after_cashier_confirmation_require_review_before_charging(): void
    {
        [$product, $ingredient] = $this->product();
        $payload = $this->payload($product);
        $payload['payment_method'] = 'qris';
        $payload['expected_total'] = $product->price;
        $product->update(['price' => 25000]);
        $this->actingAs($this->staff('karyawan'))->postJson(route('karyawan.pos.checkout'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('expected_total');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('10.00', $ingredient->fresh()->stok);
    }

    public function test_order_number_generation_continues_past_four_digits(): void
    {
        $employee = $this->staff('karyawan');
        foreach ([9999, 10000] as $number) {
            $this->order($employee, 'selesai', ['order_number' => 'CK-'.now()->format('Ymd').'-'.$number]);
        }
        $this->assertSame('CK-'.now()->format('Ymd').'-10001', DB::transaction(fn () => Order::generateOrderNumber()));
    }

    public function test_paid_pos_transaction_is_consistent_across_history_income_reports_and_exports(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('HOUR', static fn ($date) => (int) date('G', strtotime($date)), 1);
        }
        [$product] = $this->product();
        $employee = $this->staff('karyawan');
        $this->actingAs($employee)->postJson(route('karyawan.pos.checkout'), $this->payload($product))->assertOk();
        $pos = Order::firstOrFail();
        $waiting = $this->order($this->staff('pengguna'), 'selesai', ['total' => 99000, 'cashier_id' => $employee->id]);
        $this->get(route('karyawan.orders.history'))->assertOk()
            ->assertViewHas('stats', fn ($stats) => (float) $stats['total_revenue'] === 15000.0);
        $this->get(route('karyawan.income.index'))->assertOk()
            ->assertViewHas('stats', fn ($stats) => (float) $stats['total_revenue'] === 15000.0);
        $this->get(route('karyawan.dashboard'))->assertOk()
            ->assertViewHas('todayStats', fn ($stats) => (float) $stats['revenue'] === 15000.0);
        $this->actingAs($this->staff('pemilik'));
        $this->get(route('pemilik.dashboard'))->assertOk()->assertViewHas('todayRevenue', 15000);
        $this->get(route('pemilik.reports.sales'))->assertOk()->assertViewHas('totalRevenue', 15000);
        $this->get(route('pemilik.analytics.index'))->assertOk()
            ->assertViewHas('kpi', fn ($stats) => (float) $stats['revenue']['current'] === 15000.0);
        $this->get(route('pemilik.performance.employees'))->assertOk()->assertViewHas('grandTotal', 15000);
        $this->get(route('pemilik.performance.products'))->assertOk()->assertViewHas('totalRevenue', 15000);
        $csv = $this->get(route('pemilik.exports.csv'))->assertOk()->streamedContent();
        $this->assertStringContainsString($pos->order_number, $csv);
        $this->assertStringNotContainsString($waiting->order_number, $csv);
    }

    public function test_customer_spending_excludes_mobile_orders_until_pickup(): void
    {
        $employee = $this->staff('karyawan');
        $customer = $this->staff('pengguna');
        $this->order($employee, 'antrian_baru', ['customer_name' => 'Pembeli POS']);
        $this->order($customer, 'selesai', ['customer_name' => 'Belum Diambil', 'total' => 99000]);
        $this->actingAs($this->staff('pemilik'))->get(route('pemilik.customers.index'))->assertOk()
            ->assertViewHas('customers', fn ($customers) => $customers->total() === 1
                && $customers->first()->customer_name === 'Pembeli POS'
                && (float) $customers->first()->total_spent === 15000.0)
            ->assertViewHas('stats', fn ($stats) => $stats['unique_customers'] === 1);
    }

    public function test_owner_remains_read_only_for_pos_and_employee_cannot_edit_catalog(): void
    {
        [$product] = $this->product();
        $this->actingAs($this->staff('pemilik'))->get(route('karyawan.pos.index'))->assertForbidden();
        $this->postJson(route('karyawan.pos.checkout'), $this->payload($product))->assertForbidden();
        $this->actingAs($this->staff('karyawan'))->get(route('pemilik.products.index'))->assertForbidden();
        $this->assertDatabaseCount('orders', 0);
    }

    private function staff(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function product(string $slug = 'snack'): array
    {
        $category = Category::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Produk uji', 'price' => 15000, 'is_active' => true]);
        $ingredient = Ingredient::create(['nama_bahan' => 'Bahan uji', 'satuan' => 'gram', 'stok' => 10]);
        $product->ingredients()->attach($ingredient->id, ['quantity' => 1, 'variant' => $slug === 'coffee' ? 'base' : null]);

        return [$product, $ingredient];
    }

    private function payload(Product $product, array $item = []): array
    {
        return ['customer_name' => 'Pelanggan POS', 'payment_method' => 'cash', 'cash_received' => 100000,
            'items' => [array_merge(['product_id' => $product->id, 'quantity' => 1], $item)]];
    }

    private function order(User $creator, string $status, array $attributes = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'CK-AUDIT-'.Str::uuid(), 'customer_name' => $creator->name,
            'user_id' => $creator->id, 'cashier_id' => $creator->id, 'status' => $status,
            'payment_method' => 'cash', 'total' => 15000, 'paid_at' => now(),
        ], $attributes));
    }
}
