<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\SystemExportData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SystemAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function fixtures(int $stock = 10): array
    {
        $user = User::factory()->create(['role' => 'pengguna', 'is_active' => true]);
        $category = Category::create(['name' => 'Snack', 'is_active' => true]);
        $product = Product::create(['name' => 'Audit snack', 'category_id' => $category->id, 'price' => 20000, 'is_active' => true]);
        $ingredient = Ingredient::create(['nama_bahan' => 'Bahan audit', 'satuan' => 'gram', 'stok' => $stock]);
        $product->ingredients()->attach($ingredient->id, ['quantity' => 1, 'variant' => null]);
        Sanctum::actingAs($user);

        return [$user, $product, $ingredient];
    }

    private function quote(Product $product, int $quantity = 1, ?string $key = null)
    {
        return $this->postJson('/api/checkouts', ['request_key' => $key ?? (string) Str::uuid(),
            'expected_total' => (float) $product->price * $quantity,
            'items' => [['product_id' => $product->id, 'quantity' => $quantity]]]);
    }

    private function upload(array $quote)
    {
        return $this->postJson('/api/orders', ['checkout_id' => $quote['id'], 'payment_method' => 'qris',
            'items' => $quote['items'], 'payment_proof' => UploadedFile::fake()->image('proof.png')]);
    }

    public function test_price_and_recipe_are_locked_before_payment_and_retries_do_not_charge_twice(): void
    {
        [$user, $product, $ingredient] = $this->fixtures();
        $key = (string) Str::uuid();
        $quote = $this->quote($product, 2, $key)->assertCreated()->assertJsonPath('data.can_pay', true)->assertJsonPath('data.status', 'reserved')->json('data');
        $product->update(['price' => 25000]);
        $product->ingredients()->updateExistingPivot($ingredient->id, ['quantity' => 3]);
        $order = $this->upload($quote)->assertCreated()->assertJsonPath('order.total', '40000.00')->json('order');
        $this->upload($quote)->assertCreated()->assertJsonPath('order.id', $order['id']);
        $this->quote($product, 2, $key)->assertCreated()->assertJsonPath('data.order.id', $order['id']);
        $this->assertDatabaseCount('orders', 1);
        $cashier = User::factory()->create(['role' => 'karyawan', 'is_active' => true]);
        $this->actingAs($cashier, 'web')->patch(route('karyawan.orders.verify', $order['id']))->assertSessionHas('success');
        $this->assertSame('8.00', $ingredient->fresh()->stok);
        $this->assertDatabaseHas('checkout_reservations', ['mobile_checkout_id' => $quote['id'], 'quantity' => 2]);
        $this->assertNotNull(DB::table('checkout_reservations')->first()->released_at);
    }

    public function test_changed_total_is_rejected_before_qris_and_shared_requirements_cannot_be_reserved(): void
    {
        [, $product, $ingredient] = $this->fixtures(1);
        $this->postJson('/api/checkouts', ['request_key' => (string) Str::uuid(), 'expected_total' => 15000,
            'items' => [['product_id' => $product->id, 'quantity' => 1]]])->assertUnprocessable()->assertJsonValidationErrors('expected_total');
        $other = Product::create(['name' => 'Other snack', 'category_id' => $product->category_id, 'price' => 10000, 'is_active' => true]);
        $other->ingredients()->attach($ingredient->id, ['quantity' => 1, 'variant' => null]);
        $this->postJson('/api/checkouts', ['request_key' => (string) Str::uuid(),
            'items' => [['product_id' => $product->id, 'quantity' => 1], ['product_id' => $other->id, 'quantity' => 1]]])
            ->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('mobile_checkouts', 0);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_reservations_reduce_both_catalogue_and_pos_capacity_and_cancel_restores_it(): void
    {
        [$user, $product, $ingredient] = $this->fixtures(1);
        $quote = $this->quote($product)->assertCreated()->json('data');
        $this->getJson('/api/products')->assertJsonPath('data.0.stock_quantities.default', 0);
        $this->assertSame('1.00', $ingredient->fresh()->stok);
        Sanctum::actingAs(User::factory()->create(['role' => 'pengguna', 'is_active' => true]));
        $this->quote($product)->assertUnprocessable();
        $cashier = User::factory()->create(['role' => 'karyawan', 'is_active' => true]);
        $this->actingAs($cashier, 'web')->postJson(route('karyawan.pos.checkout'), ['customer_name' => 'POS', 'payment_method' => 'cash',
            'cash_received' => 20000, 'items' => [['product_id' => $product->id, 'quantity' => 1]]])->assertUnprocessable();
        Sanctum::actingAs($user);
        $this->deleteJson('/api/checkouts/'.$quote['id'])->assertOk();
        $this->quote($product, 1, $quote['request_key'])->assertCreated()->assertJsonPath('data.can_pay', false);
        $this->getJson('/api/products')->assertJsonPath('data.0.stock_quantities.default', 1);
    }

    public function test_pending_proof_retains_stock_after_the_original_window_and_cannot_be_cancelled(): void
    {
        [$user, $product] = $this->fixtures(1);
        $quote = $this->quote($product)->assertCreated()->json('data');
        $this->upload($quote)->assertCreated();
        $this->travel(20)->minutes();
        $this->getJson('/api/products')->assertJsonPath('data.0.stock_quantities.default', 0);
        $this->deleteJson('/api/checkouts/'.$quote['id'])->assertConflict();
        $this->travelBack();
    }

    public function test_expired_paid_proof_is_kept_for_review_and_manual_refund_is_audited(): void
    {
        [$customer, $product, $ingredient] = $this->fixtures(1);
        $quote = $this->quote($product)->assertCreated()->json('data');
        $this->travel(16)->minutes();
        $cashier = User::factory()->create(['role' => 'karyawan', 'is_active' => true]);
        $this->actingAs($cashier, 'web')->postJson(route('karyawan.pos.checkout'), ['customer_name' => 'POS', 'payment_method' => 'cash',
            'cash_received' => 20000, 'items' => [['product_id' => $product->id, 'quantity' => 1]]])->assertOk();
        Sanctum::actingAs($customer);
        $id = $this->upload($quote)->assertCreated()->json('order.id');
        $this->assertNotNull(Order::findOrFail($id)->payment_review_reason);
        $this->actingAs($cashier, 'web')->patch(route('karyawan.orders.verify', $id))->assertSessionHas('error');
        $this->patch(route('karyawan.orders.resolve-payment', $id), ['action' => 'refund', 'note' => 'Dana dikembalikan melalui transfer ref 12345', 'confirmed' => 1])->assertSessionHas('success');
        $order = Order::findOrFail($id);
        $this->assertSame('dikembalikan', $order->status);
        $this->assertSame($cashier->id, $order->payment_resolved_by);
        $this->assertNotNull($order->payment_resolved_at);
        $this->assertSame('0.00', $ingredient->fresh()->stok);
        $this->assertFalse(Order::visibleInTransactionHistory()->whereKey($id)->exists());
        $this->assertFalse(Order::activeQueue()->whereKey($id)->exists());
        $this->patch(route('karyawan.orders.resolve-payment', $id), ['action' => 'reject', 'note' => 'Tindakan ulang tidak boleh berhasil', 'confirmed' => 1])->assertSessionHas('error');
        Sanctum::actingAs($customer);
        $this->getJson('/api/orders/history')->assertJsonPath('data.0.status', 'dikembalikan');
        $this->travelBack();
    }

    public function test_rejection_requires_the_assigned_employee_and_confirmation_then_releases_stock(): void
    {
        [$customer, $product, $ingredient] = $this->fixtures(1);
        $quote = $this->quote($product)->assertCreated()->json('data');
        $id = $this->upload($quote)->assertCreated()->json('order.id');
        $cashier = User::factory()->create(['role' => 'karyawan', 'is_active' => true]);
        Order::findOrFail($id)->update(['cashier_id' => $cashier->id]);
        $route = route('karyawan.orders.resolve-payment', $id);
        $data = ['action' => 'reject', 'note' => 'Mutasi diperiksa; pembayaran belum diterima.', 'confirmed' => 1];
        $this->actingAs(User::factory()->create(['role' => 'karyawan', 'is_active' => true]), 'web')->patch($route, $data)->assertSessionHas('error');
        $this->actingAs(User::factory()->create(['role' => 'pemilik', 'is_active' => true]), 'web')->patch($route, $data)->assertForbidden();
        $this->actingAs($cashier, 'web')->patchJson($route, ['action' => 'reject', 'note' => $data['note']])->assertUnprocessable()->assertJsonValidationErrors('confirmed');
        $this->assertNull(DB::table('checkout_reservations')->first()->released_at);
        $this->patch($route, $data)->assertSessionHas('success');
        $this->assertDatabaseHas('orders', ['id' => $id, 'status' => 'dibatalkan', 'paid_at' => null, 'payment_resolved_by' => $cashier->id]);
        $this->assertNotNull(DB::table('checkout_reservations')->first()->released_at);
        $this->assertSame('1.00', $ingredient->fresh()->stok);
        $this->assertSame(1, $product->fresh()->availableQuantity());
        $this->assertFalse(Order::activeQueue()->whereKey($id)->exists());
        $this->assertFalse(Order::visibleInTransactionHistory()->whereKey($id)->exists());
        Sanctum::actingAs($customer);
        $this->upload($quote)->assertCreated()->assertJsonPath('order.id', $id)->assertJsonPath('order.status', 'dibatalkan');
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_quote_is_private_and_cannot_be_used_for_a_different_cart(): void
    {
        [$user, $product] = $this->fixtures();
        $quote = $this->quote($product)->assertCreated()->json('data');
        Sanctum::actingAs(User::factory()->create(['role' => 'pengguna', 'is_active' => true]));
        $this->upload($quote)->assertForbidden();
        $this->deleteJson('/api/checkouts/'.$quote['id'])->assertForbidden();
        Sanctum::actingAs($user);
        $quote['items'][0]['quantity'] = 2;
        $this->upload($quote)->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_legacy_direct_upload_cannot_bypass_the_reserve_before_payment_contract(): void
    {
        [, $product] = $this->fixtures();
        $this->postJson('/api/orders', ['payment_method' => 'qris', 'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_proof' => UploadedFile::fake()->image('proof.png')])->assertStatus(426);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_old_apps_are_stopped_at_catalogue_preflight_and_qris_before_paying(): void
    {
        $this->fixtures();
        $this->withHeader('X-Ciks-Checkout-Version', '1')->getJson('/api/products')->assertStatus(426);
        $this->getJson('/api/shop/qris')->assertStatus(426);
    }

    public function test_export_usage_multiplies_portions_and_excludes_unverified_materials(): void
    {
        [$user, $product, $ingredient] = $this->fixtures(100);
        $product->ingredients()->updateExistingPivot($ingredient->id, ['quantity' => 2]);
        $quote = $this->quote($product, 3)->assertCreated()->json('data');
        $id = $this->upload($quote)->assertCreated()->json('order.id');
        $sections = app(SystemExportData::class)->sections([]);
        $this->assertCount(0, ($sections['pemakaian_bahan']['rows'])()->all());
        $this->actingAs(User::factory()->create(['role' => 'karyawan', 'is_active' => true]), 'web')->patch(route('karyawan.orders.verify', $id))->assertSessionHas('success');
        $rows = ($sections['pemakaian_bahan']['rows'])()->values()->all();
        $this->assertCount(1, $rows);
        $this->assertEquals(6, $rows[0][5]);
        $this->assertSame('94.00', $ingredient->fresh()->stok);
    }

    public function test_customers_with_active_paid_orders_or_open_payment_cannot_be_deactivated(): void
    {
        [$customer, $product] = $this->fixtures();
        $quote = $this->quote($product)->assertCreated()->json('data');
        $owner = User::factory()->create(['role' => 'pemilik', 'is_active' => true]);
        $this->actingAs($owner, 'web')->patch(route('pemilik.users.toggle-status', $customer))->assertSessionHas('error');
        Sanctum::actingAs($customer);
        $id = $this->upload($quote)->assertCreated()->json('order.id');
        $cashier = User::factory()->create(['role' => 'karyawan', 'is_active' => true]);
        $this->actingAs($cashier, 'web')->patch(route('karyawan.orders.verify', $id))->assertSessionHas('success');
        $this->patch(route('karyawan.orders.update-status', $id), ['status' => 'sedang_dibuat'])->assertSessionHas('success');
        $this->patch(route('karyawan.orders.update-status', $id), ['status' => 'selesai'])->assertSessionHas('success');
        $this->actingAs($owner, 'web')->patch(route('pemilik.users.toggle-status', $customer))->assertSessionHas('error');
        $this->assertTrue($customer->fresh()->is_active);
        Sanctum::actingAs($customer);
        $this->postJson('/api/orders/'.$id.'/pickup')->assertOk();
        $this->actingAs($owner, 'web')->patch(route('pemilik.users.toggle-status', $customer))->assertSessionHas('success');
    }

    public function test_reserved_ingredient_units_stock_and_quoted_products_cannot_be_invalidated(): void
    {
        [, $product, $ingredient] = $this->fixtures(2);
        $this->quote($product, 2)->assertCreated();
        $owner = User::factory()->create(['role' => 'pemilik', 'is_active' => true]);
        $this->actingAs($owner, 'web')->putJson(route('pemilik.materials.update', $ingredient), [
            'nama_bahan' => $ingredient->nama_bahan, 'stok' => 1, 'expected_stock' => 2, 'satuan' => 'gram'])->assertUnprocessable();
        $this->delete(route('pemilik.products.destroy', $product))->assertSessionHas('error');
        $this->assertNotNull($product->fresh());
    }

    public function test_distinct_mobile_accounts_with_same_name_stay_separate_from_each_other_and_pos(): void
    {
        [$user, $product] = $this->fixtures();
        $user->update(['name' => 'Andi']);
        $second = User::factory()->create(['name' => 'Andi', 'role' => 'pengguna', 'is_active' => true]);
        $cashier = User::factory()->create(['role' => 'karyawan', 'is_active' => true]);
        foreach ([$user, $second, $cashier] as $index => $actor) {
            $order = Order::create(['order_number' => 'AUDIT-'.$index, 'user_id' => $actor->id, 'customer_name' => 'Andi',
                'status' => $actor->isPengguna() ? 'diambil' : 'selesai', 'payment_method' => 'qris', 'total' => 20000, 'paid_at' => now()]);
            $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 1, 'price' => 20000, 'subtotal' => 20000]);
        }
        $this->actingAs(User::factory()->create(['role' => 'pemilik', 'is_active' => true]), 'web')->get(route('pemilik.customers.index'))->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['unique_customers'] === 3 && $stats['returning_customers'] === 0)
            ->assertViewHas('customers', fn ($customers) => $customers->count() === 3);
    }

    public function test_legacy_image_route_blocks_proofs_and_path_traversal_even_when_public_file_exists(): void
    {
        Storage::disk('public')->put('payment_proofs/legacy.png', 'private proof');
        Storage::disk('public')->put('products/public.png', 'public image');
        $this->get('/api/image/payment_proofs/legacy.png')->assertNotFound();
        $this->get('/api/image//payment_proofs/legacy.png')->assertNotFound();
        $this->get('/api/image/./payment_proofs/legacy.png')->assertNotFound();
        $this->get('/storage/payment_proofs/legacy.png')->assertNotFound();
        $this->get('/api/image/products/public.png')->assertOk();
        $this->get('/api/image/../private.png')->assertNotFound();
    }
}
