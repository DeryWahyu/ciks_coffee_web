<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaryawanOrderWorkflowFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_cannot_skip_status_or_bypass_qris_verification(): void
    {
        $employee = $this->employee();
        $customer = $this->customer();
        $regularOrder = $this->makeOrder($customer, [
            'status' => 'antrian_baru',
            'payment_method' => 'cash',
        ]);
        $qrisOrder = $this->makeOrder($customer, [
            'status' => 'menunggu_verifikasi',
            'payment_method' => 'qris',
            'payment_proof' => 'payment_proofs/bukti.png',
        ]);

        $this->actingAs($employee)
            ->withSession(['_token' => 'test-token'])
            ->from('/karyawan/orders')
            ->patch(route('karyawan.orders.update-status', $regularOrder), ['status' => 'selesai', '_token' => 'test-token'])
            ->assertRedirect('/karyawan/orders')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('orders', [
            'id' => $regularOrder->id,
            'status' => 'antrian_baru',
            'cashier_id' => null,
        ]);

        $this->actingAs($employee)
            ->withSession(['_token' => 'test-token'])
            ->from('/karyawan/orders')
            ->patch(route('karyawan.orders.update-status', $qrisOrder), ['status' => 'sedang_dibuat', '_token' => 'test-token'])
            ->assertRedirect('/karyawan/orders')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('orders', [
            'id' => $qrisOrder->id,
            'status' => 'menunggu_verifikasi',
            'cashier_id' => null,
        ]);

        $this->actingAs($employee)
            ->withSession(['_token' => 'test-token'])
            ->from('/karyawan/orders')
            ->patch(route('karyawan.orders.update-status', $regularOrder), ['status' => 'sedang_dibuat', '_token' => 'test-token'])
            ->assertRedirect('/karyawan/orders')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id' => $regularOrder->id,
            'status' => 'sedang_dibuat',
            'cashier_id' => $employee->id,
        ]);
    }

    public function test_qris_verification_only_deducts_stock_once(): void
    {
        $employee = $this->employee();
        $customer = $this->customer();
        $ingredient = Ingredient::create([
            'nama_bahan' => 'Kopi Arabika',
            'satuan' => 'gram',
            'stok' => 10,
        ]);
        $category = Category::create([
            'name' => 'Minuman',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Americano',
            'price' => 15000,
            'is_active' => true,
        ]);
        $product->ingredients()->attach($ingredient->id, [
            'quantity' => 2,
            'variant' => null,
        ]);
        $order = $this->makeOrder($customer, [
            'status' => 'menunggu_verifikasi',
            'payment_method' => 'qris',
            'payment_proof' => 'payment_proofs/bukti.png',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'price' => $product->price,
            'subtotal' => $product->price,
        ]);

        $this->actingAs($employee)
            ->withSession(['_token' => 'test-token'])
            ->from('/karyawan/orders')
            ->patch(route('karyawan.orders.verify', $order), ['_token' => 'test-token'])
            ->assertRedirect('/karyawan/orders')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'antrian_baru',
            'cashier_id' => $employee->id,
        ]);
        $this->assertEquals(8.0, (float) $ingredient->fresh()->stok);

        $this->actingAs($employee)
            ->withSession(['_token' => 'test-token'])
            ->from('/karyawan/orders')
            ->patch(route('karyawan.orders.verify', $order), ['_token' => 'test-token'])
            ->assertRedirect('/karyawan/orders')
            ->assertSessionHas('error');

        $this->assertEquals(8.0, (float) $ingredient->fresh()->stok);
    }

    public function test_qris_verification_rejects_a_legacy_item_whose_recipe_is_missing(): void
    {
        $employee = $this->employee();
        $customer = $this->customer();
        $category = Category::create([
            'name' => 'Makanan',
            'slug' => 'makanan',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Produk Tanpa Resep',
            'price' => 15000,
            'is_active' => true,
        ]);
        $order = $this->makeOrder($customer, [
            'status' => 'menunggu_verifikasi',
            'payment_method' => 'qris',
            'payment_proof' => 'payment_proofs/bukti.png',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'price' => $product->price,
            'subtotal' => $product->price,
        ]);

        $this->actingAs($employee)
            ->from('/karyawan/orders')
            ->patch(route('karyawan.orders.verify', $order))
            ->assertRedirect('/karyawan/orders')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'menunggu_verifikasi',
            'cashier_id' => null,
        ]);
    }

    public function test_employee_delete_endpoint_is_unavailable_and_order_is_preserved(): void
    {
        $employee = $this->employee();
        $order = $this->makeOrder($this->customer(), [
            'status' => 'menunggu_verifikasi',
            'payment_method' => 'qris',
            'payment_proof' => 'payment_proofs/bukti.png',
        ]);

        $this->actingAs($employee)
            ->withSession(['_token' => 'test-token'])
            ->delete("/karyawan/orders/{$order->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_shared_queue_keeps_pos_until_selesai_and_mobile_until_diambil_across_days(): void
    {
        $viewer = $this->employee();
        $handler = $this->employee();
        $customer = $this->customer();

        $posActive = $this->makeOrder($handler, [
            'customer_name' => 'Pelanggan POS Aktif',
            'cashier_id' => $handler->id,
            'status' => 'sedang_dibuat',
        ]);
        $posFinished = $this->makeOrder($handler, [
            'customer_name' => 'Pelanggan POS Selesai',
            'cashier_id' => $handler->id,
            'status' => 'selesai',
        ]);
        $mobileReady = $this->makeOrder($customer, [
            'customer_name' => 'Pelanggan Mobile Siap',
            'cashier_id' => $handler->id,
            'status' => 'selesai',
        ]);
        $mobilePickedUp = $this->makeOrder($customer, [
            'customer_name' => 'Pelanggan Mobile Diambil',
            'cashier_id' => $handler->id,
            'status' => 'diambil',
        ]);
        $mobileNew = $this->makeOrder($customer, [
            'customer_name' => 'Pelanggan Mobile Baru',
            'cashier_id' => null,
            'status' => 'antrian_baru',
        ]);

        foreach ([$posActive, $posFinished, $mobileReady, $mobilePickedUp, $mobileNew] as $order) {
            $order->forceFill([
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ])->saveQuietly();
        }

        $response = $this->actingAs($viewer)
            ->get(route('karyawan.orders.index'));

        $response->assertOk()
            ->assertSeeText($posActive->order_number)
            ->assertSeeText($mobileReady->order_number)
            ->assertSeeText($mobileNew->order_number)
            ->assertDontSeeText($posFinished->order_number)
            ->assertDontSeeText($mobilePickedUp->order_number)
            ->assertSeeText('Ditangani karyawan lain')
            ->assertViewHas('orders', fn ($orders) => $orders->total() === 3)
            ->assertViewHas('counts', fn ($counts) => $counts === [
                'menunggu_verifikasi' => 0,
                'antrian_baru' => 1,
                'sedang_dibuat' => 1,
                'selesai' => 1,
            ]);

        $this->assertDatabaseCount('orders', 5);
    }

    public function test_history_detail_and_income_remain_private_per_employee(): void
    {
        $employee = $this->employee();
        $otherEmployee = $this->employee();
        $customer = $this->customer();

        $ownOrder = $this->makeOrder($customer, [
            'customer_name' => 'Transaksi Milik Sendiri',
            'cashier_id' => $employee->id,
            'status' => 'diambil',
            'total' => 15000,
        ]);
        $otherOrder = $this->makeOrder($customer, [
            'customer_name' => 'Transaksi Milik Karyawan Lain',
            'cashier_id' => $otherEmployee->id,
            'status' => 'diambil',
            'total' => 99000,
        ]);

        $this->actingAs($employee)
            ->get(route('karyawan.orders.history'))
            ->assertOk()
            ->assertSeeText($ownOrder->order_number)
            ->assertDontSeeText($otherOrder->order_number)
            ->assertViewHas('orders', fn ($orders) => $orders->total() === 1);

        $this->actingAs($employee)
            ->get(route('karyawan.orders.show', $otherOrder))
            ->assertForbidden();

        $this->actingAs($employee)
            ->get(route('karyawan.income.index'))
            ->assertOk()
            ->assertViewHas('stats', function ($stats) {
                return $stats['total_transactions'] === 1
                    && (float) $stats['total_revenue'] === 15000.0;
            });

        $this->assertDatabaseCount('orders', 2);
    }

    private function employee(): User
    {
        return User::factory()->create([
            'role' => 'karyawan',
            'is_active' => true,
        ]);
    }

    private function customer(): User
    {
        return User::factory()->create([
            'role' => 'pengguna',
            'is_active' => true,
        ]);
    }

    private function makeOrder(User $orderOwner, array $attributes = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'CK-TEST-'.str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT),
            'customer_name' => $orderOwner->name,
            'user_id' => $orderOwner->id,
            'payment_method' => 'cash',
            'total' => 15000,
            'status' => 'antrian_baru',
        ], $attributes));
    }
}
