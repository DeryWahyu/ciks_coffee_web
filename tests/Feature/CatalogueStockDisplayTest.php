<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatalogueStockDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_owner_and_pos_expose_identical_portion_counts_without_exposing_recipes(): void
    {
        $product = $this->product('coffee', 0.60, 0.70);
        $product->update(['price_lite' => 12000]);
        $ingredient = $product->ingredients()->first();
        $product->ingredients()->attach($ingredient->id, ['quantity' => 0.20, 'variant' => 'lite']);

        Sanctum::actingAs($this->user('pengguna'));
        $mobile = $this->getJson('/api/products')->assertOk()
            ->assertJsonPath('data.0.stock_quantities', ['base' => 0, 'lite' => 3])
            ->assertJsonPath('data.0.is_available', true)
            ->assertJsonMissingPath('data.0.ingredients')
            ->assertJsonMissingPath('data.0.stok')
            ->assertJsonMissingPath('data.0.pivot')
            ->json('data.0.stock_quantities');

        $this->actingAs($this->user('pemilik'), 'web');
        $this->getJson(route('pemilik.products.stock', ['ids' => [$product->id]]))
            ->assertOk()->assertJsonPath("products.{$product->id}.stock_quantities", $mobile);
        $this->get(route('pemilik.products.index'))->assertOk()->assertSee('Base: 0 · Lite: 3 porsi');

        $this->actingAs($this->user('karyawan'), 'web');
        $this->getJson(route('karyawan.pos.detail', $product))->assertOk()->assertJsonPath('stock_quantities', $mobile);
    }

    public function test_mobile_stocks_refresh_after_pos_uses_a_shared_ingredient(): void
    {
        $first = $this->product('snack', 0.60, 0.20);
        $second = Product::create(['name' => 'Produk kedua', 'category_id' => $first->category_id, 'price' => 10000, 'is_active' => true]);
        $second->ingredients()->attach($first->ingredients()->first()->id, ['quantity' => 0.30, 'variant' => null]);
        $this->actingAs($this->user('karyawan'))->postJson(route('karyawan.pos.checkout'), [
            'customer_name' => 'Uji stok', 'payment_method' => 'cash', 'cash_received' => 100000,
            'items' => [['product_id' => $first->id, 'quantity' => 3]],
        ])->assertOk();

        Sanctum::actingAs($this->user('pengguna'));
        $products = $this->getJson('/api/products')->assertOk()->json('data');
        foreach ($products as $product) {
            $this->assertSame(['default' => 0], $product['stock_quantities']);
            $this->assertFalse($product['is_available']);
        }
    }

    public function test_owner_sold_out_cards_keep_edit_controls_available(): void
    {
        $product = $this->product('snack', 0.19, 0.20);
        $response = $this->actingAs($this->user('pemilik'))
            ->get(route('pemilik.products.index'))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $card = $xpath->query('//*[@id="product-'.$product->id.'"]')->item(0);
        $this->assertStringContainsString('is-sold-out', $card->getAttribute('class'));
        $this->assertStringContainsString('Sisa: 0 porsi', $card->textContent);
        $this->assertStringContainsString('Stok habis', $card->textContent);
        $this->assertSame(1, $xpath->query('.//a[@href="'.route('pemilik.products.edit', $product).'"]', $card)->length);
        $this->assertSame(0, $xpath->query('.//button[@disabled]', $card)->length);
    }

    public function test_owner_distinguishes_inactive_products_from_out_of_stock_products(): void
    {
        $product = $this->product('snack', 10, 1);
        $product->update(['is_active' => false]);
        $this->actingAs($this->user('pemilik'))->get(route('pemilik.products.index'))
            ->assertOk()->assertSee('Produk atau kategori nonaktif');
        $this->getJson(route('pemilik.products.stock', ['ids' => [$product->id]]))
            ->assertOk()->assertJsonPath("products.{$product->id}.is_active", false)
            ->assertJsonPath("products.{$product->id}.stock_quantities.default", 0);
    }

    public function test_owner_stock_snapshot_requires_owner_access_and_limits_requested_products(): void
    {
        $product = $this->product('snack', 1, 1);
        $url = route('pemilik.products.stock', ['ids' => [$product->id]]);
        $this->getJson($url)->assertUnauthorized();
        $this->actingAs($this->user('karyawan'))->getJson($url)->assertForbidden();
        $this->actingAs($this->user('pemilik'))->getJson(route('pemilik.products.stock', ['ids' => range(1, 13)]))
            ->assertUnprocessable()->assertJsonValidationErrors('ids');
        $this->getJson($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function product(string $slug, float $stock, float $required): Product
    {
        $category = Category::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
        $product = Product::create(['name' => 'Produk uji', 'category_id' => $category->id, 'price' => 15000, 'is_active' => true]);
        $ingredient = Ingredient::create(['nama_bahan' => 'Bahan internal', 'satuan' => 'gram', 'stok' => $stock]);
        $product->ingredients()->attach($ingredient->id, ['quantity' => $required, 'variant' => $slug === 'coffee' ? 'base' : null]);

        return $product;
    }
}
