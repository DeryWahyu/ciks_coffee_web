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

class MobileQrisIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_cannot_bypass_qris_verification_by_forging_cash_payment(): void
    {
        [$product, $ingredient] = $this->product();
        Sanctum::actingAs($this->user('pengguna'));
        $this->postJson('/api/orders', ['payment_method' => 'cash', 'cash_received' => 20000,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('payment_method');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('10.00', $ingredient->fresh()->stok);
    }

    public function test_identical_proof_retries_return_one_order_and_cannot_be_reused_for_another_cart(): void
    {
        Storage::fake('local');
        Event::fake([MobileOrderReceived::class]);
        [$product, $ingredient] = $this->product();
        $image = UploadedFile::fake()->image('proof.png');
        $bytes = file_get_contents($image->getRealPath());
        Sanctum::actingAs($this->user('pengguna'));
        $payload = ['payment_method' => 'qris', 'items' => [['product_id' => $product->id, 'quantity' => 1]]];
        $first = $this->postJson('/api/orders', $payload + [
            'payment_proof' => UploadedFile::fake()->createWithContent('proof.png', $bytes),
        ])->assertCreated();
        $ingredient->update(['stok' => 0]);
        $retry = $this->postJson('/api/orders', $payload + [
            'payment_proof' => UploadedFile::fake()->createWithContent('proof.png', $bytes),
        ])->assertCreated();
        $this->assertSame($first->json('order.id'), $retry->json('order.id'));
        $this->assertDatabaseCount('orders', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles());
        Event::assertDispatchedTimes(MobileOrderReceived::class, 1);
        $payload['items'][0]['quantity'] = 2;
        $this->postJson('/api/orders', $payload + [
            'payment_proof' => UploadedFile::fake()->createWithContent('proof.png', $bytes),
        ])->assertUnprocessable()->assertJsonValidationErrors('payment_proof');
        $this->assertDatabaseCount('orders', 1);
        Storage::disk('local')->assertExists(Order::firstOrFail()->payment_proof);
    }

    public function test_missing_proof_file_cannot_be_verified_or_consume_stock(): void
    {
        Storage::fake('local');
        [$product, $ingredient] = $this->product();
        $customer = $this->user('pengguna');
        $order = Order::create(['order_number' => 'CK-MISSING-PROOF', 'user_id' => $customer->id,
            'customer_name' => $customer->name, 'payment_method' => 'qris', 'payment_proof' => 'payment_proofs/missing.png',
            'status' => 'menunggu_verifikasi', 'total' => 15000]);
        $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name,
            'quantity' => 1, 'price' => 15000, 'subtotal' => 15000]);
        $this->actingAs($this->user('karyawan'))->patch(route('karyawan.orders.verify', $order))->assertSessionHas('error');
        $this->assertSame('menunggu_verifikasi', $order->fresh()->status);
        $this->assertNull($order->fresh()->paid_at);
        $this->assertSame('10.00', $ingredient->fresh()->stok);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function product(): array
    {
        $category = Category::create(['name' => 'Snack', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Produk QRIS', 'price' => 15000, 'is_active' => true]);
        $ingredient = Ingredient::create(['nama_bahan' => 'Bahan QRIS', 'satuan' => 'gram', 'stok' => 10]);
        $product->ingredients()->attach($ingredient->id, ['quantity' => 1, 'variant' => null]);

        return [$product, $ingredient];
    }
}
