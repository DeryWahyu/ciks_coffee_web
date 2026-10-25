<?php

namespace Tests\Feature;

use App\Events\MobileOrderReceived;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MenuCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class MenuCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_has_three_available_products_per_category_with_real_images_and_variant_recipes(): void
    {
        Storage::fake('public');
        $this->seed(MenuCatalogSeeder::class);

        $this->assertDatabaseCount('categories', 6);
        $this->assertDatabaseCount('products', 18);
        $this->assertDatabaseCount('ingredients', 36);
        $this->assertSame(0, Ingredient::doesntHave('products')->count());
        foreach (Category::withCount('products')->get() as $category) {
            $this->assertSame(3, $category->products_count);
        }

        foreach (Product::with('category')->get() as $product) {
            $this->assertTrue($product->isAvailable(), $product->name);
            $this->assertGreaterThan(0, (float) $product->price);
            Storage::disk('public')->assertExists($product->image);
            $info = getimagesize(Storage::disk('public')->path($product->image));
            $this->assertSame(IMAGETYPE_PNG, $info[2]);

            if ($product->category->isCoffee()) {
                $this->assertGreaterThan(0, (float) $product->price_lite);
                $this->assertLessThan((float) $product->price, (float) $product->price_lite);
                $this->assertNotEmpty($product->ingredientsByVariant('base'));
                $this->assertNotEmpty($product->ingredientsByVariant('lite'));
            } else {
                $this->assertNull($product->price_lite);
                $this->assertNotEmpty($product->ingredientsByVariant(null));
                $this->assertSame(0, $product->ingredients()->wherePivotNotNull('variant')->count());
            }
        }
    }

    public function test_reseeding_preserves_stock_prices_recipes_images_and_existing_account_passwords(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $product = Product::where('name', 'Kopi Susu Gula Aren')->firstOrFail();
        $coffee = Ingredient::where('nama_bahan', 'Biji Kopi')->firstOrFail();
        $coffee->update(['stok' => 987.50]);
        $product->update(['price' => 27000, 'price_lite' => 23000, 'is_active' => false]);
        $product->ingredients()->wherePivot('variant', 'lite')->updateExistingPivot($coffee->id, ['quantity' => 11]);
        Storage::disk('public')->put($product->image, 'operator-managed-image');
        $owner = User::where('email', 'pemilik@cikscoffee.com')->firstOrFail();
        $owner->update(['password' => Hash::make('sandi-pemilik-pribadi')]);
        $originalHash = $owner->password;
        $links = DB::table('ingredient_product')->count();

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('products', 18);
        $this->assertDatabaseCount('ingredients', 36);
        $this->assertSame($links, DB::table('ingredient_product')->count());
        $this->assertSame('987.50', $coffee->fresh()->stok);
        $this->assertSame('27000.00', $product->fresh()->price);
        $this->assertSame('23000.00', $product->fresh()->price_lite);
        $this->assertFalse($product->fresh()->is_active);
        $this->assertEquals(11, $product->ingredientsByVariant('lite')->firstWhere('id', $coffee->id)->pivot->quantity);
        $this->assertSame('operator-managed-image', Storage::disk('public')->get($product->image));
        $this->assertSame($originalHash, $owner->fresh()->password);
    }

    public function test_lite_checkout_uses_seeded_price_and_deducts_the_lite_recipe_exactly_once(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Event::fake([MobileOrderReceived::class]);
        $this->seed(MenuCatalogSeeder::class);
        $product = Product::where('name', 'Kopi Susu Gula Aren')->firstOrFail();
        Sanctum::actingAs(User::factory()->create(['role' => 'pengguna', 'is_active' => true]));

        $response = $this->postJson('/api/orders', [
            'payment_method' => 'qris', 'payment_proof' => UploadedFile::fake()->image('proof.png'),
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'variant' => 'lite', 'price' => 1]],
        ])->assertCreated();

        $this->assertDatabaseHas('orders', ['id' => $response->json('order.id'), 'total' => 42000, 'change_amount' => null, 'paid_at' => null]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'variant' => 'lite', 'price' => 21000, 'quantity' => 2, 'subtotal' => 42000]);
        $this->assertDatabaseHas('ingredients', ['nama_bahan' => 'Biji Kopi', 'stok' => 5000]);
        $this->actingAs(User::factory()->create(['role' => 'karyawan', 'is_active' => true]), 'web')
            ->patch(route('karyawan.orders.verify', $response->json('order.id')))->assertSessionHas('success');
        $this->patch(route('karyawan.orders.verify', $response->json('order.id')))->assertSessionHas('error');
        foreach (['Biji Kopi' => 4976, 'Susu UHT' => 19700, 'Sirup Gula Aren' => 2960, 'Air Mineral' => 29920, 'Es Batu' => 19760] as $name => $stock) {
            $this->assertDatabaseHas('ingredients', ['nama_bahan' => $name, 'stok' => $stock]);
        }
    }

    public function test_an_existing_incompatible_stock_unit_aborts_without_partial_catalog_writes(): void
    {
        Storage::fake('public');
        Ingredient::create(['nama_bahan' => 'Biji Kopi', 'satuan' => 'kg', 'stok' => 9]);

        try {
            $this->seed(MenuCatalogSeeder::class);
            $this->fail('Seeder must reject a stock unit mismatch.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Satuan bahan Biji Kopi berbeda', $exception->getMessage());
        }

        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('ingredients', 1);
        $this->assertDatabaseHas('ingredients', ['nama_bahan' => 'Biji Kopi', 'satuan' => 'kg', 'stok' => 9]);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
