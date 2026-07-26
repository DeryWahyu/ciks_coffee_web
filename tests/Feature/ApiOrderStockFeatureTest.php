<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiOrderStockFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_coffee_requires_an_explicit_base_or_lite_variant(): void
    {
        $user = $this->customer();
        $coffee = $this->product($this->category('coffee'));
        $ingredient = $this->ingredient(10);
        $this->attachIngredient($coffee, $ingredient, 2, 'base');

        $this->checkout($user, [[
            'product_id' => $coffee->id,
            'quantity' => 1,
        ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('10.00', $ingredient->fresh()->stok);
    }

    public function test_non_coffee_rejects_a_variant_instead_of_skipping_its_recipe(): void
    {
        $user = $this->customer();
        $snack = $this->product($this->category('snack'));
        $ingredient = $this->ingredient(10);
        $this->attachIngredient($snack, $ingredient, 2, null);

        $this->checkout($user, [[
            'product_id' => $snack->id,
            'variant' => 'base',
            'quantity' => 1,
        ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('10.00', $ingredient->fresh()->stok);
    }

    public function test_inactive_product_cannot_be_ordered_through_the_api(): void
    {
        $user = $this->customer();
        $product = $this->product($this->category('snack'), false);

        $this->checkout($user, [[
            'product_id' => $product->id,
            'quantity' => 1,
        ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_shared_ingredient_requirement_is_aggregated_before_creating_an_order(): void
    {
        $user = $this->customer();
        $category = $this->category('snack');
        $firstProduct = $this->product($category, true, 'Roti A');
        $secondProduct = $this->product($category, true, 'Roti B');
        $ingredient = $this->ingredient(5);

        $this->attachIngredient($firstProduct, $ingredient, 3, null);
        $this->attachIngredient($secondProduct, $ingredient, 3, null);

        $this->checkout($user, [
            ['product_id' => $firstProduct->id, 'quantity' => 1],
            ['product_id' => $secondProduct->id, 'quantity' => 1],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('5.00', $ingredient->fresh()->stok);
    }

    public function test_valid_checkout_deducts_the_aggregated_stock_once(): void
    {
        $user = $this->customer();
        $category = $this->category('snack');
        $firstProduct = $this->product($category, true, 'Roti A');
        $secondProduct = $this->product($category, true, 'Roti B');
        $ingredient = $this->ingredient(10);

        $this->attachIngredient($firstProduct, $ingredient, 3, null);
        $this->attachIngredient($secondProduct, $ingredient, 2, null);

        $this->checkout($user, [
            ['product_id' => $firstProduct->id, 'quantity' => 1],
            ['product_id' => $secondProduct->id, 'quantity' => 1],
        ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame('5.00', $ingredient->fresh()->stok);
    }

    public function test_qris_checkout_requires_a_payment_proof_and_does_not_deduct_stock(): void
    {
        $user = $this->customer();
        $product = $this->product($this->category('snack'));
        $ingredient = $this->ingredient(10);
        $this->attachIngredient($product, $ingredient, 2, null);

        Sanctum::actingAs($user);

        $this->postJson('/api/orders', [
            'payment_method' => 'qris',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payment_proof');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('10.00', $ingredient->fresh()->stok);
    }

    public function test_qris_checkout_with_a_payment_proof_waits_for_verification_without_deducting_stock(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $user = $this->customer();
        $product = $this->product($this->category('snack'));
        $ingredient = $this->ingredient(10);
        $this->attachIngredient($product, $ingredient, 2, null);

        Sanctum::actingAs($user);

        $this->post('/api/orders', [
            'payment_method' => 'qris',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_proof' => UploadedFile::fake()->image('bukti-qris.png'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('order.status', 'menunggu_verifikasi');

        $this->assertDatabaseHas('orders', [
            'payment_method' => 'qris',
            'status' => 'menunggu_verifikasi',
        ]);
        $this->assertSame('10.00', $ingredient->fresh()->stok);

        $order = Order::firstOrFail();
        Storage::disk('local')->assertExists($order->payment_proof);
        Storage::disk('public')->assertMissing($order->payment_proof);
    }

    public function test_order_creation_is_limited_to_five_requests_per_minute_per_customer(): void
    {
        $user = $this->customer();
        $product = $this->product($this->category('snack'));
        $payload = [
            'payment_method' => 'cash',
            'cash_received' => 100000,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        Sanctum::actingAs($user);

        foreach (range(1, 5) as $_) {
            $this->postJson('/api/orders', $payload)->assertCreated();
        }

        $this->postJson('/api/orders', $payload)->assertTooManyRequests();
    }

    private function customer(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_PENGGUNA,
            'is_active' => true,
        ]);
    }

    private function category(string $slug): Category
    {
        return Category::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function product(Category $category, bool $isActive = true, string $name = 'Produk Uji'): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'price' => 15000,
            'is_active' => $isActive,
        ]);
    }

    private function ingredient(float $stock): Ingredient
    {
        return Ingredient::create([
            'nama_bahan' => 'Bahan Uji',
            'satuan' => 'pcs',
            'stok' => $stock,
        ]);
    }

    private function attachIngredient(Product $product, Ingredient $ingredient, float $quantity, ?string $variant): void
    {
        $product->ingredients()->attach($ingredient->id, [
            'quantity' => $quantity,
            'variant' => $variant,
        ]);
    }

    private function checkout(User $user, array $items)
    {
        Sanctum::actingAs($user);

        return $this->postJson('/api/orders', [
            'payment_method' => 'cash',
            'cash_received' => 100000,
            'items' => $items,
        ]);
    }
}
