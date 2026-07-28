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

    public function test_employee_history_lists_every_order_without_changing_any_order(): void
    {
        $employee = $this->employee();
        $otherEmployee = $this->employee();
        $customer = $this->customer();

        $ownOrder = $this->makeOrder($customer, [
            'cashier_id' => $employee->id,
            'status' => 'diambil',
        ]);
        $otherOrder = $this->makeOrder($customer, [
            'cashier_id' => $otherEmployee->id,
            'status' => 'selesai',
        ]);
        $unassignedOrder = $this->makeOrder($customer, [
            'cashier_id' => null,
            'status' => 'antrian_baru',
        ]);

        $response = $this->actingAs($employee)
            ->get(route('karyawan.orders.history'));

        $response->assertOk()
            ->assertSeeText($ownOrder->order_number)
            ->assertSeeText($otherOrder->order_number)
            ->assertSeeText($unassignedOrder->order_number)
            ->assertViewHas('orders', fn ($orders) => $orders->total() === 3);

        $this->assertDatabaseCount('orders', 3);
        $this->assertDatabaseHas('orders', [
            'id' => $ownOrder->id,
            'cashier_id' => $employee->id,
            'status' => 'diambil',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $otherOrder->id,
            'cashier_id' => $otherEmployee->id,
            'status' => 'selesai',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $unassignedOrder->id,
            'cashier_id' => null,
            'status' => 'antrian_baru',
        ]);
    }

    public function test_employee_can_read_another_employee_order_from_history_but_cannot_change_it(): void
    {
        $employee = $this->employee();
        $otherEmployee = $this->employee();
        $order = $this->makeOrder($this->customer(), [
            'cashier_id' => $otherEmployee->id,
            'status' => 'antrian_baru',
        ]);

        $this->actingAs($employee)
            ->get(route('karyawan.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('id', $order->id);

        $this->actingAs($employee)
            ->withSession(['_token' => 'test-token'])
            ->from(route('karyawan.orders.history'))
            ->patch(route('karyawan.orders.update-status', $order), [
                'status' => 'sedang_dibuat',
                '_token' => 'test-token',
            ])
            ->assertRedirect(route('karyawan.orders.history'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'cashier_id' => $otherEmployee->id,
            'status' => 'antrian_baru',
        ]);
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

    private function makeOrder(User $customer, array $attributes = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'CK-TEST-'.str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT),
            'customer_name' => $customer->name,
            'user_id' => $customer->id,
            'payment_method' => 'cash',
            'total' => 15000,
            'status' => 'antrian_baru',
        ], $attributes));
    }
}
