<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CoffeeTable;
use App\Models\FloorLayout;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;

class IndonesianErrorMessagesTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'pengguna'): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function record(string $label, $response, int $status = 422): void
    {
        $response->assertStatus($status);
        $data = $response->json();
        $text = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->assertDoesNotMatchRegularExpression('/validation\.|Unauthenticated|Unauthorized|Too Many|CSRF token mismatch|No query results|and \d+ more error/', $text, $label);
        $this->assertNotEmpty($data['message'] ?? null, $label);
    }

    private function product(): Product
    {
        $category = Category::create(['name' => 'Coffee', 'is_active' => true]);
        $product = Product::create(['name' => 'Kopi Audit', 'category_id' => $category->id, 'price' => 20000, 'price_lite' => 15000, 'is_active' => true]);
        $ingredient = Ingredient::create(['nama_bahan' => 'Bahan Audit', 'satuan' => 'gram', 'stok' => 50]);
        $product->ingredients()->attach($ingredient->id, ['quantity' => 1, 'variant' => 'base']);

        return $product;
    }

    private function table(): CoffeeTable
    {
        return CoffeeTable::create(['floor_layout_id' => FloorLayout::sole()->id, 'code' => 'A01', 'name' => 'Meja audit', 'capacity' => 2, 'shape' => 'square', 'position_x' => 20, 'position_y' => 20, 'width' => 10, 'height' => 10, 'rotation' => 0, 'status' => 'available', 'is_active' => true, 'version' => 1]);
    }

    public function test_owner_validation_surfaces(): void
    {
        $this->actingAs($this->user('pemilik'), 'web');
        $product = $this->product();
        $category = $product->category;
        $other = Category::create(['name' => 'Snack', 'is_active' => true]);
        $table = $this->table();
        $this->record('owner.category.update.duplicate', $this->putJson('/pemilik/categories/'.$other->id, ['name' => 'Coffee']));
        $this->record('owner.category.create.too_long', $this->postJson('/pemilik/categories', ['name' => str_repeat('a', 101)]));
        $this->record('owner.material.create.negative_stock', $this->postJson('/pemilik/materials', ['nama_bahan' => 'Bahan lain', 'satuan' => 'gram', 'stok' => -1]));
        $ingredient = Ingredient::firstOrFail();
        $this->record('owner.material.update.missing_expected_stock', $this->putJson('/pemilik/materials/'.$ingredient->id, ['nama_bahan' => $ingredient->nama_bahan, 'satuan' => 'gram', 'stok' => 50]));
        $this->record('owner.user.create.email_format', $this->postJson('/pemilik/users', ['name' => 'Staff audit', 'email' => 'email-tidak-valid', 'password' => 'validpass123', 'password_confirmation' => 'validpass123']));
        $base = ['name' => 'Menu audit', 'category_id' => $category->id, 'price' => 10000, 'is_active' => true];
        $this->record('owner.product.create.negative_price', $this->postJson('/pemilik/products', [...$base, 'price' => -1]));
        $this->record('owner.product.create.invalid_image', $this->postJson('/pemilik/products', [...$base, 'image' => UploadedFile::fake()->create('note.txt', 1, 'text/plain')]));
        $this->record('owner.product.create.missing_recipe', $this->postJson('/pemilik/products', $base));
        $this->record('owner.product.create.invalid_recipe_quantity', $this->postJson('/pemilik/products', [...$base, 'ingredients_base' => [['id' => $ingredient->id, 'quantity' => 0]]]));
        $this->record('owner.table.layout.invalid_canvas', $this->putJson('/pemilik/meja/layout/'.$table->floor_layout_id, ['tables' => [], 'canvas_width' => 399]));
        $this->record('owner.table.status.note_too_long', $this->patchJson('/pemilik/meja/'.$table->id.'/status', ['status' => 'available', 'version' => 1, 'note' => str_repeat('x', 501)]));
        $this->record('owner.table.archive.invalid_boolean', $this->patchJson('/pemilik/meja/'.$table->id.'/toggle-active', ['is_active' => 'invalid', 'version' => 1]));
        $this->record('owner.table.delete.invalid_version', $this->deleteJson('/pemilik/meja/'.$table->id, ['version' => 0]));
        $this->record('owner.report.date_format', $this->getJson('/pemilik/reports/sales?start_date=bad-date'));
        $this->record('owner.report.date_order', $this->getJson('/pemilik/reports/sales?start_date=2026-11-17&end_date=2026-11-01'));
        $this->record('owner.export.invalid_scope', $this->getJson('/pemilik/exports/csv?scope=invalid'));
        $this->record('owner.qris.invalid_file', $this->postJson('/pemilik/settings/qris', ['qris_image' => UploadedFile::fake()->create('note.txt', 1, 'text/plain')]));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_employee_validation_surfaces(): void
    {
        $product = $this->product();
        $table = $this->table();
        $customer = $this->user();
        $order = Order::create(['order_number' => 'MSG-AUDIT', 'customer_name' => $customer->name, 'user_id' => $customer->id, 'payment_method' => 'qris', 'total' => 20000, 'status' => 'menunggu_verifikasi']);
        $this->actingAs($this->user('karyawan'), 'web');
        $base = ['customer_name' => 'Audit', 'payment_method' => 'cash', 'items' => [['product_id' => $product->id, 'variant' => 'base', 'quantity' => 1]]];
        $this->record('employee.pos.cash_missing', $this->postJson('/karyawan/pos/checkout', $base));
        $this->record('employee.pos.fractional_quantity', $this->postJson('/karyawan/pos/checkout', [...$base, 'cash_received' => 20000, 'items' => [['product_id' => $product->id, 'variant' => 'base', 'quantity' => 1.5]]]));
        $this->record('employee.payment_resolution.short_note', $this->patchJson('/karyawan/orders/'.$order->id.'/resolve-payment', ['action' => 'reject', 'note' => 'a', 'confirmed' => 0]));
        $this->record('employee.table.status.note_too_long', $this->patchJson('/karyawan/meja/'.$table->id.'/status', ['status' => 'available', 'version' => 1, 'note' => str_repeat('x', 501)]));
        $this->record('employee.table.delete.invalid_version', $this->deleteJson('/karyawan/meja/'.$table->id, ['version' => 0]));
        $this->assertSame('menunggu_verifikasi', $order->fresh()->status);
    }

    public function test_mobile_api_validation_surfaces(): void
    {
        $product = $this->product();
        $customer = $this->user();
        Sanctum::actingAs($customer);
        $this->record('mobile.register.duplicate_email', $this->postJson('/api/register', ['name' => 'Audit', 'email' => $customer->email, 'phone' => '08123456', 'password' => 'validpass123']));
        $this->record('mobile.register.long_phone', $this->postJson('/api/register', ['name' => 'Audit', 'email' => 'new@example.test', 'phone' => str_repeat('1', 21), 'password' => 'validpass123']));
        $this->record('mobile.login.invalid_email', $this->postJson('/api/login', ['email' => 'tidak-valid', 'password' => 'validpass123']));
        $this->record('mobile.checkout.invalid_key_and_quantity', $this->postJson('/api/checkouts', ['request_key' => 'bad', 'items' => [['product_id' => $product->id, 'variant' => 'base', 'quantity' => 0]]]));
        $this->record('mobile.order.invalid_proof_format', $this->postJson('/api/orders', ['checkout_id' => (string) Str::uuid(), 'payment_method' => 'qris', 'items' => [['product_id' => $product->id, 'variant' => 'base', 'quantity' => 1]], 'payment_proof' => UploadedFile::fake()->image('proof.gif')]));
        $this->record('mobile.order.missing_proof', $this->postJson('/api/orders', ['payment_method' => 'qris', 'items' => [['product_id' => $product->id, 'variant' => 'base', 'quantity' => 1]]]));
        $foreign = $this->user();
        $order = Order::create(['order_number' => 'FOREIGN', 'customer_name' => $foreign->name, 'user_id' => $foreign->id, 'payment_method' => 'qris', 'total' => 20000, 'status' => 'selesai']);
        $this->record('mobile.pickup.foreign_order', $this->postJson('/api/orders/'.$order->id.'/pickup'), 403);
        $this->record('mobile.order.missing_resource', $this->getJson('/api/orders/999999/payment-proof'), 404);
        $this->assertDatabaseCount('mobile_checkouts', 0);
    }

    public function test_unauthenticated_and_throttled_messages(): void
    {
        $this->record('mobile.unauthenticated', $this->getJson('/api/products'), 401);
        for ($i = 0; $i < 7; $i++) {
            $response = $this->postJson('/api/login', ['email' => 'invalid', 'password' => 'validpass123']);
            if ($response->status() === 429) {
                $this->record('mobile.login.rate_limited', $response, 429);
                break;
            }
        }
        $this->assertSame(429, $response->status());
    }

    public function test_default_http_error_pages(): void
    {
        config(['app.debug' => false]);
        $request = Request::create('/audit-error-page', 'GET');
        $request->headers->set('Accept', 'text/html');
        $handler = app(ExceptionHandler::class);
        foreach ([403, 404, 419, 429, 500, 503] as $status) {
            $response = $handler->render($request, new HttpException($status));
            $this->assertSame($status, $response->getStatusCode());
            preg_match('~<title>(.*?)</title>~s', $response->getContent(), $match);
            $this->assertStringContainsString('Ciks Coffee', $match[1] ?? '');
            $this->assertStringContainsString('lang="id"', $response->getContent());
            $this->assertDoesNotMatchRegularExpression('/Forbidden|Not Found|Page Expired|Too Many Requests|Server Error|Service Unavailable/', $match[1] ?? '');
        }
    }

    public function test_missing_recipe_error_is_visible_after_normal_form_redirect(): void
    {
        $this->actingAs($this->user('pemilik'), 'web');
        $product = $this->product();
        $response = $this->from('/pemilik/products/create')->post('/pemilik/products', ['name' => 'Menu audit', 'category_id' => $product->category_id, 'price' => 20000, 'is_active' => 1]);
        $response->assertRedirect('/pemilik/products/create')->assertSessionHasErrors('ingredients_base');
        $message = session('errors')->get('ingredients_base')[0];
        $page = $this->withCookie(config('session.cookie'), session()->getId())->get('/pemilik/products/create')->assertOk();
        $visible = str_contains(strip_tags($page->getContent()), $message);
        $this->assertTrue($visible);
        $page->assertSee('data-validation-summary', false);
    }

    public function test_normal_form_errors_remain_visible_after_redirect(): void
    {
        $owner = $this->user('pemilik');
        $target = $this->user('karyawan');
        $this->actingAs($owner, 'web');
        $this->from('/pemilik/users?tab=karyawan')->patch('/pemilik/users/'.$target->id.'/reset-password', ['password' => 'validpass123', 'password_confirmation' => 'anotherpass123'])->assertRedirect()->assertSessionHasErrors('password');
        $message = session('errors')->get('password')[0];
        $page = $this->withCookie(config('session.cookie'), session()->getId())->get('/pemilik/users?tab=karyawan')->assertOk();
        $page->assertSeeText($message)->assertSee('data-validation-summary', false);
        $this->from('/pemilik/reports/sales')->get('/pemilik/reports/sales?start_date=2026-11-17&end_date=2026-11-01')->assertRedirect()->assertSessionHasErrors('end_date');
        $message = session('errors')->get('end_date')[0];
        $page = $this->withCookie(config('session.cookie'), session()->getId())->get('/pemilik/reports/sales')->assertOk();
        $page->assertSeeText($message)->assertSee('data-validation-summary', false);
        $customer = $this->user();
        $order = Order::create(['order_number' => 'VISIBILITY', 'user_id' => $customer->id, 'customer_name' => $customer->name, 'total' => 20000, 'status' => 'menunggu_verifikasi', 'payment_method' => 'qris']);
        $this->actingAs($target, 'web')->from('/karyawan/orders')->patch('/karyawan/orders/'.$order->id.'/resolve-payment', ['action' => 'reject', 'note' => 'a', 'confirmed' => 0])->assertRedirect()->assertSessionHasErrors(['note', 'confirmed']);
        $messages = session('errors')->all();
        $page = $this->withCookie(config('session.cookie'), session()->getId())->get('/karyawan/orders')->assertOk();
        foreach ($messages as $message) {
            $page->assertSeeText($message);
        }
        config(['app.debug' => false]);
        $handler = app(ExceptionHandler::class);
        $request = Request::create('/audit-expired', 'POST');
        $request->headers->set('Accept', 'application/json');
        $response = $handler->render($request, new TokenMismatchException('CSRF token mismatch.'));
        $this->assertSame(419, $response->getStatusCode());
        $this->assertStringContainsString('Sesi formulir telah berakhir', json_decode($response->getContent(), true)['message']);
    }

    public function test_clear_messages_as_control_cases(): void
    {
        $this->record('web.login.required.already_clear', $this->postJson('/login', []));
        $table = $this->table();
        $this->actingAs($this->user('pemilik'), 'web');
        $this->record('owner.table.duplicate.already_fixed', $this->postJson('/pemilik/meja', ['floor_layout_id' => $table->floor_layout_id, 'code' => 'A01', 'name' => 'Meja audit', 'capacity' => 2, 'shape' => 'square', 'position_x' => 20, 'position_y' => 20, 'width' => 10, 'height' => 10]));
        $product = $this->product();
        $this->actingAs($this->user('karyawan'), 'web');
        $this->record('employee.pos.stock_short.already_clear', $this->postJson('/karyawan/pos/checkout', ['customer_name' => 'Audit', 'payment_method' => 'qris', 'items' => [['product_id' => $product->id, 'variant' => 'base', 'quantity' => 51]]]));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_api_errors_are_json_even_without_an_accept_header_and_retain_retry_headers(): void
    {
        $this->get('/api/products')->assertUnauthorized()->assertJsonPath('message', 'Sesi Anda telah berakhir. Silakan masuk kembali untuk melanjutkan.');
        $request = Request::create('/api/login', 'POST');
        $handler = app(ExceptionHandler::class);
        $response = $handler->render($request, new TooManyRequestsHttpException(60));
        $this->assertSame('60', $response->headers->get('Retry-After'));
        $this->assertSame(429, $response->getStatusCode());
        $this->assertStringContainsString('Terlalu banyak percobaan', json_decode($response->getContent(), true)['message']);
    }
}
