<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAccountAndCatalogSecurityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_api_token_is_rejected_and_revoked_after_account_is_deactivated(): void
    {
        $user = $this->customer();
        $plainTextToken = $user->createToken('mobile_auth_token')->plainTextToken;

        $user->update(['is_active' => false]);

        $this->withToken($plainTextToken)
            ->getJson('/api/products')
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Akun Anda telah dinonaktifkan.',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_owner_deactivation_revokes_all_tokens_issued_to_the_user(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_PEMILIK,
            'is_active' => true,
        ]);
        $user = $this->customer();
        $user->createToken('mobile-one');
        $user->createToken('mobile-two');

        $this->actingAs($owner)
            ->patch(route('pemilik.users.toggle-status', $user))
            ->assertRedirect(route('pemilik.users.index', ['tab' => User::ROLE_PENGGUNA]));

        $this->assertFalse($user->fresh()->is_active);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_product_catalog_exposes_only_mobile_safe_fields(): void
    {
        $category = Category::create([
            'name' => 'Makanan',
            'slug' => 'makanan',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Roti Aman',
            'description' => 'Deskripsi publik',
            'image' => 'products/roti.webp',
            'price' => 15000,
            'is_active' => true,
        ]);
        $ingredient = Ingredient::create([
            'nama_bahan' => 'Bahan Resep Rahasia',
            'satuan' => 'gram',
            'stok' => 9876,
        ]);
        $product->ingredients()->attach($ingredient->id, [
            'quantity' => 12.34,
            'variant' => null,
        ]);

        Sanctum::actingAs($this->customer());

        $response = $this->getJson('/api/products')->assertOk();
        $payload = $response->json('data.0');

        $this->assertEqualsCanonicalizing([
            'id',
            'category_id',
            'name',
            'description',
            'image_url',
            'price',
            'price_lite',
            'category',
            'is_available',
            'unavailable_reason',
        ], array_keys($payload));
        $this->assertEqualsCanonicalizing(['id', 'name', 'slug'], array_keys($payload['category']));
        $this->assertTrue($payload['is_available']);
        $this->assertStringNotContainsString('Bahan Resep Rahasia', $response->getContent());
        $this->assertStringNotContainsString('12.34', $response->getContent());
        $this->assertArrayNotHasKey('ingredients', $payload);
        $this->assertArrayNotHasKey('pivot', $payload);
        $this->assertArrayNotHasKey('is_active', $payload);
        $this->assertArrayNotHasKey('created_at', $payload);
        $this->assertArrayNotHasKey('updated_at', $payload);
    }

    public function test_unavailable_reason_does_not_reveal_ingredient_name_or_stock(): void
    {
        $category = Category::create([
            'name' => 'Makanan',
            'slug' => 'makanan',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Roti Terbatas',
            'price' => 15000,
            'is_active' => true,
        ]);
        $ingredient = Ingredient::create([
            'nama_bahan' => 'Nama Bahan Internal',
            'satuan' => 'gram',
            'stok' => 0,
        ]);
        $product->ingredients()->attach($ingredient->id, [
            'quantity' => 5,
            'variant' => null,
        ]);

        Sanctum::actingAs($this->customer());

        $response = $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.is_available', false)
            ->assertJsonPath('data.0.unavailable_reason', 'Produk sedang tidak tersedia.');

        $this->assertStringNotContainsString('Nama Bahan Internal', $response->getContent());
    }

    private function customer(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_PENGGUNA,
            'is_active' => true,
        ]);
    }
}
