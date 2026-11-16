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

    public function test_pos_rejects_an_active_product_without_a_recipe(): void
    {
        $product = $this->product($this->category('snack'));

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

    public function test_pos_displays_portions_limited_by_the_scarcest_ingredient(): void
    {
        $product = $this->product($this->category('snack'));
        $this->attachIngredient($product, $this->ingredient(1.25), 0.25, null);
        $this->attachIngredient($product, $this->ingredient(0.59), 0.20, null);

        $this->assertSame(2, $product->availableQuantity());
        $this->actingAs(User::factory()->create(['role' => User::ROLE_KARYAWAN, 'is_active' => true]))
            ->get(route('karyawan.pos.index'))
            ->assertOk()->assertSee('Sisa: 2 porsi');

        $this->getJson(route('karyawan.pos.stock'))
            ->assertOk()->assertJsonPath("products.{$product->id}.stock_quantities.default", 2)
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_sold_out_card_is_a_disabled_button_with_a_clear_label(): void
    {
        $product = $this->product($this->category('snack'));
        $this->attachIngredient($product, $this->ingredient(0.19), 0.20, null);

        $response = $this->actingAs(User::factory()->create(['role' => User::ROLE_KARYAWAN, 'is_active' => true]))
            ->get(route('karyawan.pos.index'))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $buttons = $xpath->query('//button[@data-id="'.$product->id.'"]');
        $this->assertCount(1, $buttons);
        $this->assertTrue($buttons->item(0)->hasAttribute('disabled'));
        $this->assertSame('true', $buttons->item(0)->getAttribute('aria-disabled'));
        $this->assertStringContainsString('Stok habis', $buttons->item(0)->textContent);
        $this->assertStringContainsString('Sisa: 0 porsi', $buttons->item(0)->textContent);
    }

    public function test_coffee_remains_selectable_when_only_lite_has_stock(): void
    {
        $product = $this->product($this->category('coffee'));
        $product->update(['price_lite' => 12000]);
        $ingredient = $this->ingredient(0.30);
        $this->attachIngredient($product, $ingredient, 0.40, 'base');
        $this->attachIngredient($product, $ingredient, 0.10, 'lite');

        $this->assertSame(0, $product->availableQuantity('base'));
        $this->assertSame(3, $product->availableQuantity('lite'));
        $this->actingAs(User::factory()->create(['role' => User::ROLE_KARYAWAN, 'is_active' => true]))
            ->getJson(route('karyawan.pos.detail', $product))
            ->assertOk()
            ->assertJsonPath('stock_quantities.base', 0)
            ->assertJsonPath('stock_quantities.lite', 3);

        $response = $this->get(route('karyawan.pos.index'))->assertOk()->assertSee('Base: 0 · Lite: 3 porsi');
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $button = (new \DOMXPath($document))->query('//button[@data-id="'.$product->id.'"]')->item(0);
        $this->assertFalse($button->hasAttribute('disabled'));
    }

    public function test_unsold_variants_and_invalid_recipes_have_zero_stock(): void
    {
        $category = $this->category('coffee');
        $coffee = $this->product($category);
        $ingredient = $this->ingredient(10);
        $this->attachIngredient($coffee, $ingredient, 1, 'lite');
        $this->assertSame(0, $coffee->availableQuantity('lite'));
        $this->assertSame(0, $coffee->availableQuantity('base'));
        $this->assertSame(0, $coffee->availableQuantity());

        $invalid = $this->product($category, true, 'Resep nol');
        $this->attachIngredient($invalid, $ingredient, 0, 'base');
        $this->assertSame(0, $invalid->availableQuantity('base'));
        $ingredient->update(['stok' => -1]);
        $valid = $this->product($category, true, 'Stok negatif');
        $this->attachIngredient($valid, $ingredient, 1, 'base');
        $this->assertSame(0, $valid->availableQuantity('base'));
    }

    public function test_duplicate_recipe_rows_are_aggregated_when_counting_portions(): void
    {
        $product = $this->product($this->category('snack'));
        $ingredient = $this->ingredient(1);
        $this->attachIngredient($product, $ingredient, 0.30, null);
        $this->attachIngredient($product, $ingredient, 0.30, null);
        $this->assertSame(1, $product->availableQuantity());
    }

    public function test_stock_snapshot_updates_every_product_sharing_an_ingredient_after_checkout(): void
    {
        $category = $this->category('snack');
        $first = $this->product($category, true, 'Roti A');
        $second = $this->product($category, true, 'Roti B');
        $ingredient = $this->ingredient(0.60);
        $this->attachIngredient($first, $ingredient, 0.30, null);
        $this->attachIngredient($second, $ingredient, 0.20, null);
        $this->checkout([['product_id' => $first->id, 'quantity' => 2]])->assertOk();
        $this->getJson(route('karyawan.pos.stock'))->assertOk()
            ->assertJsonPath("products.{$first->id}.stock_quantities.default", 0)
            ->assertJsonPath("products.{$second->id}.stock_quantities.default", 0);
        $this->assertSame('0.00', $ingredient->fresh()->stok);
    }

    public function test_stock_snapshot_omits_inactive_products_and_categories_and_requires_employee_access(): void
    {
        $category = $this->category('snack');
        $inactive = $this->product($category, false);
        $hiddenCategory = $this->category('meal');
        $hiddenCategory->update(['is_active' => false]);
        $hidden = $this->product($hiddenCategory);
        $this->assertSame(0, $hidden->availableQuantity());
        $this->assertSame(0, $inactive->availableQuantity());

        $this->getJson(route('karyawan.pos.stock'))->assertUnauthorized();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_PEMILIK, 'is_active' => true]))
            ->getJson(route('karyawan.pos.stock'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_KARYAWAN, 'is_active' => true]))
            ->getJson(route('karyawan.pos.stock'))->assertOk()->assertExactJson(['products' => []]);
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
