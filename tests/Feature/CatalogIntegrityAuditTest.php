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
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatalogIntegrityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_edit_keeps_its_current_category_selectable_after_deactivation(): void
    {
        [$product] = $this->product();
        $this->actingAs($this->user('pemilik'))->get(route('pemilik.products.edit', $product))
            ->assertOk()->assertViewHas('categories', fn ($categories) => $categories->contains('id', $product->category_id));
        $product->category->update(['is_active' => false]);
        $this->get(route('pemilik.products.edit', $product))
            ->assertOk()->assertViewHas('categories', fn ($categories) => $categories->contains('id', $product->category_id));
    }

    public function test_pending_qris_deducts_the_original_recipe_and_records_payment_at_verification(): void
    {
        Storage::fake('local');
        [$product, $ingredient] = $this->product();
        Sanctum::actingAs($this->user('pengguna'));
        $response = $this->postJson('/api/orders', [
            'payment_method' => 'qris', 'payment_proof' => UploadedFile::fake()->image('proof.png'),
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertCreated()->assertJsonPath('order.paid_at', null);
        $order = Order::findOrFail($response->json('order.id'));
        $this->assertSame('10.00', $ingredient->fresh()->stok);
        $this->assertDatabaseHas('order_item_ingredients', ['order_item_id' => $order->items->first()->id, 'quantity' => 1]);
        $this->actingAs($this->user('pemilik'), 'web')
            ->putJson(route('pemilik.products.update', $product), [
                'name' => $product->name, 'category_id' => $product->category_id,
                'price' => 18000, 'is_active' => true,
                'ingredients' => [['id' => $ingredient->id, 'quantity' => 3]],
            ])->assertRedirect();
        $this->travel(2)->minutes();
        $this->actingAs($this->user('karyawan'), 'web')
            ->patch(route('karyawan.orders.verify', $order))->assertSessionHas('success');
        $this->assertSame('8.00', $ingredient->fresh()->stok);
        $this->assertSame('30000.00', $order->fresh()->total);
        $this->assertGreaterThan($order->created_at->timestamp, $order->fresh()->paid_at->timestamp);
        $this->patch(route('karyawan.orders.verify', $order))->assertSessionHas('error');
        $this->assertSame('8.00', $ingredient->fresh()->stok);
    }

    public function test_stale_stock_edit_cannot_overwrite_a_pos_deduction_or_change_a_used_unit(): void
    {
        [$product, $ingredient] = $this->product();
        $this->actingAs($this->user('karyawan'))->postJson(route('karyawan.pos.checkout'), [
            'customer_name' => 'Pembeli', 'payment_method' => 'cash', 'cash_received' => 20000,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertOk();
        $this->actingAs($this->user('pemilik'));
        $payload = ['nama_bahan' => $ingredient->nama_bahan, 'satuan' => 'gram', 'stok' => 20, 'expected_stock' => 10];
        $this->putJson(route('pemilik.materials.update', $ingredient), $payload)->assertUnprocessable();
        $this->assertSame('9.00', $ingredient->fresh()->stok);
        $payload['expected_stock'] = 9;
        $this->putJson(route('pemilik.materials.update', $ingredient), $payload)->assertRedirect();
        $payload['expected_stock'] = 20;
        $payload['satuan'] = 'kg';
        $this->putJson(route('pemilik.materials.update', $ingredient), $payload)->assertUnprocessable();
        $this->assertSame('gram', $ingredient->fresh()->satuan);
        $this->assertSame('20.00', $ingredient->fresh()->stok);
    }

    public function test_duplicate_recipe_materials_are_rejected_without_changing_price_or_recipe(): void
    {
        [$product, $ingredient] = $this->product();
        $this->actingAs($this->user('pemilik'))->putJson(route('pemilik.products.update', $product), [
            'name' => $product->name, 'category_id' => $product->category_id, 'price' => 99000,
            'ingredients' => [['id' => $ingredient->id, 'quantity' => 1], ['id' => $ingredient->id, 'quantity' => 2]],
        ])->assertUnprocessable();
        $this->assertSame('15000.00', $product->fresh()->price);
        $this->assertCount(1, $product->fresh()->ingredients);
        $this->assertEquals(1, $product->fresh()->ingredients->first()->pivot->quantity);
    }

    public function test_active_products_require_a_recipe_and_unconfigured_drafts_cannot_be_activated(): void
    {
        $category = Category::create(['name' => 'Snack', 'is_active' => true]);
        $payload = ['name' => 'Produk tanpa resep', 'category_id' => $category->id, 'price' => 15000, 'is_active' => true];
        $this->actingAs($this->user('pemilik'))->postJson(route('pemilik.products.store'), $payload)->assertUnprocessable();
        $this->assertDatabaseCount('products', 0);
        $payload['is_active'] = false;
        $this->postJson(route('pemilik.products.store'), $payload)->assertRedirect();
        $draft = Product::firstOrFail();
        $this->patch(route('pemilik.products.toggle-status', $draft))->assertSessionHas('error');
        $this->assertFalse($draft->fresh()->is_active);
    }

    public function test_catalog_keeps_a_valid_lite_variant_available_when_base_stock_runs_out(): void
    {
        [$product, $base] = $this->product('coffee');
        $base->update(['stok' => 0]);
        $lite = Ingredient::create(['nama_bahan' => 'Bahan Lite', 'satuan' => 'gram', 'stok' => 5]);
        $product->update(['price_lite' => 10000]);
        $product->ingredients()->attach($lite->id, ['quantity' => 1, 'variant' => 'lite']);
        Sanctum::actingAs($this->user('pengguna'));
        $this->getJson('/api/products')->assertOk()->assertJsonPath('data.0.is_available', true);
        $this->actingAs($this->user('karyawan'), 'web')->postJson(route('karyawan.pos.checkout'), [
            'customer_name' => 'Pembeli Lite', 'payment_method' => 'cash', 'cash_received' => 10000,
            'items' => [['product_id' => $product->id, 'variant' => 'lite', 'quantity' => 1]],
        ])->assertOk();
        $this->assertSame('4.00', $lite->fresh()->stok);
        $this->assertSame('0.00', $base->fresh()->stok);
    }

    public function test_failed_mobile_checkout_does_not_leave_orphan_payment_proofs(): void
    {
        Storage::fake('local');
        [$product] = $this->product();
        $product->category->update(['is_active' => false]);
        Sanctum::actingAs($this->user('pengguna'));
        $this->postJson('/api/orders', [
            'payment_method' => 'qris', 'payment_proof' => UploadedFile::fake()->image('proof.png'),
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertUnprocessable();
        $this->assertEmpty(Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_notification_failure_does_not_turn_a_committed_mobile_order_into_an_error(): void
    {
        Storage::fake('local');
        [$product] = $this->product();
        Event::listen(MobileOrderReceived::class, static fn () => throw new \RuntimeException('Audit: notification unavailable'));
        Sanctum::actingAs($this->user('pengguna'));
        $this->postJson('/api/orders', [
            'payment_method' => 'qris', 'payment_proof' => UploadedFile::fake()->image('proof.png'),
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_slug_collisions_and_invalid_analytics_periods_are_validation_errors(): void
    {
        Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $this->actingAs($this->user('pemilik'))->postJson(route('pemilik.categories.store'), ['name' => 'Coffee!'])->assertUnprocessable();
        $this->getJson(route('pemilik.analytics.index', ['period' => '9999999']))->assertUnprocessable();
    }

    public function test_owner_password_reset_revokes_existing_mobile_tokens(): void
    {
        $customer = $this->user('pengguna');
        $customer->createToken('old-device');
        $this->actingAs($this->user('pemilik'))->patch(route('pemilik.users.reset-password', $customer), [
            'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertRedirect();
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $customer->id]);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function product(string $slug = 'snack'): array
    {
        $category = Category::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
        $product = Product::create(['name' => 'Produk audit', 'category_id' => $category->id, 'price' => 15000, 'is_active' => true]);
        $ingredient = Ingredient::create(['nama_bahan' => 'Bahan audit', 'satuan' => 'gram', 'stok' => 10]);
        $product->ingredients()->attach($ingredient->id, ['quantity' => 1, 'variant' => $slug === 'coffee' ? 'base' : null]);

        return [$product, $ingredient];
    }
}
