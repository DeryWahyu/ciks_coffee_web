<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosOrderStockFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_rejects_an_inactive_product_from_a_forged_checkout_request(): void
    {
        $product = $this->product($this->category('snack'), false);

        $this->checkout([[
            'product_id' => $product->id,
            'quantity' => 1,
        ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_pos_rejects_a_variant_that_does_not_match_the_product_type(): void
    {
        $snack = $this->product($this->category('snack'));
        $ingredient = $this->ingredient(10);
        $this->attachIngredient($snack, $ingredient, 2, null);

        $this->checkout([[
            'product_id' => $snack->id,
            'variant' => 'base',
            'quantity' => 1,
        ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('10.00', $ingredient->fresh()->stok);
    }

    public function test_pos_requires_an_explicit_variant_for_coffee(): void
    {
        $coffee = $this->product($this->category('coffee'));
        $ingredient = $this->ingredient(10);
        $this->attachIngredient($coffee, $ingredient, 2, 'base');

        $this->checkout([[
            'product_id' => $coffee->id,
            'quantity' => 1,
        ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('10.00', $ingredient->fresh()->stok);
    }

    public function test_base_only_coffee_rejects_a_forged_lite_variant(): void
    {
        $coffee = $this->product($this->category('coffee'));
        $ingredient = $this->ingredient(10);
        $this->attachIngredient($coffee, $ingredient, 2, 'base');

        $this->checkout([[
            'product_id' => $coffee->id,
            'variant' => 'lite',
            'quantity' => 1,
        ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('10.00', $ingredient->fresh()->stok);
    }

    public function test_base_only_coffee_uses_the_base_recipe_and_deducts_its_stock(): void
    {
        $coffee = $this->product($this->category('coffee'));
        $ingredient = $this->ingredient(10);
        $this->attachIngredient($coffee, $ingredient, 2, 'base');

        $this->checkout([[
            'product_id' => $coffee->id,
            'variant' => 'base',
            'quantity' => 2,
        ]])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('order.items.0.quantity', 2);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $coffee->id,
            'variant' => 'base',
            'quantity' => 2,
        ]);
        $this->assertSame('6.00', $ingredient->fresh()->stok);
    }

    public function test_pos_aggregates_a_shared_ingredient_before_validating_stock(): void
    {
        $category = $this->category('snack');
        $firstProduct = $this->product($category, true, 'Roti A');
        $secondProduct = $this->product($category, true, 'Roti B');
        $ingredient = $this->ingredient(5);
        $this->attachIngredient($firstProduct, $ingredient, 3, null);
        $this->attachIngredient($secondProduct, $ingredient, 3, null);

        $this->checkout([
            ['product_id' => $firstProduct->id, 'quantity' => 1],
            ['product_id' => $secondProduct->id, 'quantity' => 1],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('5.00', $ingredient->fresh()->stok);
    }

    public function test_valid_pos_checkout_deducts_each_aggregated_ingredient_once(): void
    {
        $category = $this->category('snack');
        $firstProduct = $this->product($category, true, 'Roti A');
        $secondProduct = $this->product($category, true, 'Roti B');
        $ingredient = $this->ingredient(10);
        $this->attachIngredient($firstProduct, $ingredient, 3, null);
        $this->attachIngredient($secondProduct, $ingredient, 2, null);

        $this->checkout([
            ['product_id' => $firstProduct->id, 'quantity' => 1],
            ['product_id' => $secondProduct->id, 'quantity' => 1],
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame('5.00', $ingredient->fresh()->stok);
    }

    public function test_pos_ui_sends_base_for_base_only_coffee_and_does_not_submit_browser_price(): void
    {
        $template = file_get_contents(resource_path('views/karyawan/pos/index.blade.php'));

        $this->assertStringContainsString('if (currentProduct.is_coffee) {', $template);
        $this->assertStringContainsString(": 'base';", $template);
        $this->assertStringContainsString('quantity: i.qty}))', $template);
        $this->assertStringNotContainsString('quantity: i.qty, price: i.price', $template);
    }

    private function checkout(array $items)
    {
        $employee = User::factory()->create([
            'role' => User::ROLE_KARYAWAN,
            'is_active' => true,
        ]);

        return $this->actingAs($employee)->postJson(route('karyawan.pos.checkout'), [
            'customer_name' => 'Pelanggan POS',
            'payment_method' => 'cash',
            'cash_received' => 100000,
            'items' => $items,
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

    private function product(Category $category, bool $isActive = true, string $name = 'Produk POS'): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'price' => 15000,
            'price_lite' => null,
            'is_active' => $isActive,
        ]);
    }

    private function ingredient(float $stock): Ingredient
    {
        return Ingredient::create([
            'nama_bahan' => 'Bahan POS',
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
}
