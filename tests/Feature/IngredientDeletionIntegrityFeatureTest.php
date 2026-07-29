<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientDeletionIntegrityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_cannot_delete_an_ingredient_that_is_still_used_by_a_recipe(): void
    {
        [$ingredient, $product] = $this->recipe();

        $this->actingAs($this->owner())
            ->delete(route('pemilik.materials.destroy', $ingredient))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('ingredients', ['id' => $ingredient->id]);
        $this->assertDatabaseHas('ingredient_product', [
            'ingredient_id' => $ingredient->id,
            'product_id' => $product->id,
        ]);
        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_owner_can_delete_an_unused_ingredient(): void
    {
        $ingredient = Ingredient::create([
            'nama_bahan' => 'Bahan Tidak Terpakai',
            'satuan' => 'gram',
            'stok' => 10,
        ]);

        $this->actingAs($this->owner())
            ->delete(route('pemilik.materials.destroy', $ingredient))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('ingredients', ['id' => $ingredient->id]);
    }

    public function test_database_restricts_deleting_an_ingredient_used_by_a_recipe(): void
    {
        [$ingredient] = $this->recipe();

        $this->expectException(QueryException::class);

        $ingredient->delete();
    }

    private function recipe(): array
    {
        $category = Category::create([
            'name' => 'Makanan',
            'slug' => 'makanan',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Produk Dengan Resep',
            'price' => 15000,
            'is_active' => true,
        ]);
        $ingredient = Ingredient::create([
            'nama_bahan' => 'Bahan Terpakai',
            'satuan' => 'gram',
            'stok' => 100,
        ]);
        $product->ingredients()->attach($ingredient->id, [
            'quantity' => 5,
            'variant' => null,
        ]);

        return [$ingredient, $product];
    }

    private function owner(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_PEMILIK,
            'is_active' => true,
        ]);
    }
}
