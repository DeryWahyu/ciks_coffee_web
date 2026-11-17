<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CoffeeTable;
use App\Models\FloorLayout;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\ImageVariants;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_queries_are_bounded_and_keep_date_boundaries_zero_days_and_mobile_pickup_rules(): void
    {
        $this->travelTo(Carbon::parse('2026-11-17 12:00:00'));
        if (DB::getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('HOUR', fn ($date) => (int) date('G', strtotime($date)), 1);
        }
        $owner = $this->user('pemilik');
        $customer = $this->user('pengguna');
        $this->order($customer, 'diambil', 10000, '2026-11-17 23:59:59');
        $this->order($customer, 'selesai', 99000, '2026-11-17 12:00:00');
        $this->order($owner, 'selesai', 12000, '2026-11-11 00:00:00');
        $this->order($owner, 'selesai', 8000, '2026-11-10 23:59:59');
        $this->actingAs($owner);
        $counts = [];
        try {
            foreach ([7, 30, 90] as $period) {
                DB::flushQueryLog();
                DB::enableQueryLog();
                $response = $this->get(route('pemilik.analytics.index', ['period' => $period]))->assertOk();
                $counts[] = count(DB::getQueryLog());
                DB::disableQueryLog();
                $response->assertViewHas('salesTrend', fn ($days) => $days->count() === $period);
                if ($period === 7) {
                    $response->assertViewHas('kpi', fn ($kpi) => (float) $kpi['revenue']['current'] === 22000.0 && $kpi['orders']['current'] === 2 && (float) $kpi['revenue']['prev'] === 8000.0)
                        ->assertViewHas('salesTrend', fn ($days) => $days->first()['revenue'] === 12000.0 && $days->last()['revenue'] === 10000.0 && $days[1]['revenue'] === 0.0);
                }
            }
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
        $this->assertSame($counts[0], $counts[1]);
        $this->assertSame($counts[0], $counts[2]);
        $this->assertLessThanOrEqual(10, $counts[0]);
        $this->assertTrue(Schema::hasIndex('orders', ['created_at']));
        $this->get(route('pemilik.dashboard'))->assertOk()->assertViewHas('todayRevenue', fn ($v) => (float) $v === 10000.0)
            ->assertViewHas('last7Days', fn ($days) => $days->sum('revenue') === 22000.0);
    }

    public function test_product_derivatives_keep_originals_aspect_ratio_and_fall_back_before_preparation(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('original.png', 1200, 800)->storeAs('products', 'original.png', 'public');
        $original = Storage::disk('public')->path($path);
        $before = hash_file('sha256', $original);
        $images = app(ImageVariants::class);
        $this->assertSame('/storage/'.$path, $images->productSources($path)['src']);
        $this->assertTrue($images->prepareProduct($path));
        $sources = $images->productSources($path);
        $this->assertStringContainsString('384w', $sources['srcset']);
        $this->assertStringContainsString('768w', $sources['srcset']);
        $this->assertSame($before, hash_file('sha256', $original));
        $size = getimagesize(Storage::disk('public')->path(substr($sources['src'], strlen('/storage/'))));
        $this->assertSame([768, 512], array_slice($size, 0, 2));
        $images->prepareProduct($path);
        $this->assertCount(2, Storage::disk('public')->allFiles('optimized-media/products'));
        $images->deleteProduct($path);
        $this->assertSame('/storage/'.$path, $images->productSources($path)['src']);
        Storage::disk('public')->assertExists($path);
        $this->assertFalse($images->prepareProduct('../private.png'));
        $this->assertNull($images->productSources('products/../../private.png')['src']);
    }

    public function test_floor_derivative_preserves_logical_and_actual_canvas_dimensions(): void
    {
        Storage::fake('public');
        $images = app(ImageVariants::class);
        $original = hash_file('sha256', public_path(config('floor_plan.background.path')));
        $this->assertTrue($images->prepareFloor());
        $path = parse_url($images->floorUrl(), PHP_URL_PATH);
        $size = getimagesize(Storage::disk('public')->path(substr($path, strlen('/storage/'))));
        $this->assertSame([3508, 2481], array_slice($size, 0, 2));
        $this->assertSame($original, hash_file('sha256', public_path(config('floor_plan.background.path'))));
    }

    public function test_phone_photo_orientation_and_small_image_dimensions_are_preserved(): void
    {
        Storage::fake('public');
        $images = app(ImageVariants::class);
        $path = UploadedFile::fake()->image('portrait.jpg', 1200, 800)->storeAs('products', 'portrait.jpg', 'public');
        $file = Storage::disk('public')->path($path);
        $jpeg = file_get_contents($file);
        // One TIFF IFD entry: Orientation=6 (rotate 90 degrees clockwise).
        $exif = "Exif\0\0II".pack('vV', 42, 8).pack('v', 1).pack('vvV', 0x0112, 3, 1).pack('v', 6)."\0\0".pack('V', 0);
        file_put_contents($file, substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2));
        $before = hash_file('sha256', $file);
        if (function_exists('exif_read_data')) {
            $this->assertTrue($images->prepareProduct($path));
            $url = $images->productSources($path)['src'];
            $size = getimagesize(Storage::disk('public')->path(substr($url, strlen('/storage/'))));
            $this->assertSame([768, 1152], array_slice($size, 0, 2));
        } else {
            $this->assertFalse($images->prepareProduct($path));
            $this->assertSame('/storage/'.$path, $images->productSources($path)['src']);
        }
        $this->assertSame($before, hash_file('sha256', $file));

        $small = UploadedFile::fake()->image('small.png', 128, 80)->storeAs('products', 'small.png', 'public');
        $images->prepareProduct($small);
        $sources = $images->productSources($small);
        $this->assertSame(1, substr_count($sources['srcset'], '128w'));
        $size = getimagesize(Storage::disk('public')->path(substr($sources['src'], strlen('/storage/'))));
        $this->assertSame([128, 80], array_slice($size, 0, 2));
    }

    public function test_public_asset_cache_is_cookie_free_and_cannot_expose_private_or_legacy_payment_proofs(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $path = UploadedFile::fake()->image('public.png')->storeAs('products', 'public.png', 'public');
        $response = $this->get('/storage/'.$path)->assertOk();
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
        $this->assertSame([], $response->headers->getCookies());
        $this->get('/storage/'.$path, ['If-None-Match' => $response->headers->get('ETag')])->assertStatus(304);
        Storage::disk('public')->put('payment_proofs/legacy.png', 'private proof');
        Storage::disk('local')->put('payment_proofs/private.png', 'private proof');
        $this->get('/storage/payment_proofs/legacy.png')->assertNotFound();
        $this->get('/storage/payment_proofs/private.png')->assertNotFound();
        $this->get('/storage/products/../payment_proofs/legacy.png')->assertNotFound();
        $this->get('/login')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_stock_conditional_requests_always_detect_new_stock_and_enforce_role_access_first(): void
    {
        $category = Category::create(['name' => 'Snack', 'slug' => 'snack', 'is_active' => true]);
        $ingredient = Ingredient::create(['nama_bahan' => 'Bahan uji', 'satuan' => 'gram', 'stok' => 10]);
        $product = Product::create(['name' => 'Menu uji', 'category_id' => $category->id, 'price' => 15000, 'is_active' => true]);
        $product->ingredients()->attach($ingredient->id, ['quantity' => 1]);
        $owner = $this->user('pemilik');
        $employee = $this->user('karyawan');
        $urls = [
            [$employee, route('karyawan.pos.stock')],
            [$owner, route('pemilik.products.stock', ['ids' => [$product->id]])],
        ];
        foreach ($urls as [$user, $url]) {
            $ingredient->update(['stok' => 10]);
            $first = $this->actingAs($user)->getJson($url)->assertOk()->assertJsonPath('products.'.$product->id.'.stock_quantities.default', 10);
            $tag = $first->headers->get('ETag');
            $this->getJson($url, ['If-None-Match' => $tag])->assertStatus(304)->assertHeader('Cache-Control', 'no-store, private');
            $ingredient->update(['stok' => 0]);
            $next = $this->getJson($url, ['If-None-Match' => $tag])->assertOk()->assertJsonPath('products.'.$product->id.'.stock_quantities.default', 0);
            $this->assertNotSame($tag, $next->headers->get('ETag'));
        }
        $this->actingAs($employee)->getJson($urls[1][1], ['If-None-Match' => $tag])->assertForbidden();
    }

    public function test_layout_conditional_response_changes_when_a_table_changes(): void
    {
        $this->actingAs($this->user('pemilik'));
        $layout = FloorLayout::firstOrFail();
        $table = CoffeeTable::create(['floor_layout_id' => $layout->id, 'code' => 'P-01', 'name' => 'Meja uji', 'capacity' => 2, 'shape' => 'round', 'position_x' => 30, 'position_y' => 30, 'status' => 'available']);
        $first = $this->getJson('/pemilik/meja/data')->assertOk();
        $tag = $first->headers->get('ETag');
        $this->getJson('/pemilik/meja/data', ['If-None-Match' => $tag])->assertStatus(304);
        $table->update(['status' => 'occupied', 'version' => 2]);
        $this->getJson('/pemilik/meja/data', ['If-None-Match' => $tag])->assertOk()->assertJsonPath('data.tables.0.status', 'occupied');
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function order(User $user, string $status, int $total, string $created): void
    {
        Order::withoutEvents(function () use ($user, $status, $total, $created) {
            $order = new Order([
                'order_number' => 'PERF-'.Order::count(), 'customer_name' => $user->name, 'user_id' => $user->id,
                'payment_method' => $user->role === 'pengguna' ? 'qris' : 'cash', 'status' => $status, 'total' => $total, 'paid_at' => $created,
            ]);
            $order->created_at = $created;
            $order->save();
        });
    }
}
