<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CoffeeTable;
use App\Models\FloorLayout;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShopSetting;
use App\Models\TableStatusHistory;
use App\Models\User;
use App\Services\SystemExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;
use ZipArchive;

class FullSystemExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_default_csv_archive_covers_business_data_relationships_media_and_pickup_revenue_without_credentials(): void
    {
        [$owner, $product, $paid, $waiting, $ingredient, $table] = $this->fixtures();
        $response = $this->actingAs($owner)->get(route('pemilik.exports.csv'))->assertOk()->assertHeader('Content-Type', 'application/zip');
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));
        try {
            $manifest = json_decode($zip->getFromName('manifest.json'), true, flags: JSON_THROW_ON_ERROR);
            $this->assertCount(15, $manifest['row_counts']);
            $this->assertSame(1, $manifest['row_counts']['transaksi']);
            $this->assertSame(2, $manifest['row_counts']['pesanan']);
            $summary = $this->csvRows($zip->getFromName('ringkasan.csv'));
            $this->assertEquals(15000, array_column($summary, 1, 0)['Omzet transaksi']);
            $this->assertStringContainsString($paid->order_number, $zip->getFromName('transaksi.csv'));
            $this->assertStringNotContainsString($waiting->order_number, $zip->getFromName('transaksi.csv'));
            $this->assertStringContainsString($waiting->order_number, $zip->getFromName('pesanan.csv'));
            $items = $this->csvRows($zip->getFromName('item_pesanan.csv'));
            $this->assertSame([(string) $paid->items->first()->id, (string) $paid->id, $paid->order_number, (string) $product->id, "'=Produk Uji", 'base', '1'], array_slice($items[1], 0, 7));
            $this->assertEquals(15000, $items[1][7]);
            $this->assertEquals(15000, $items[1][8]);
            $this->assertSame(1, $manifest['row_counts']['pemakaian_bahan']);
            $this->assertSame(2, $manifest['row_counts']['resep']);
            $this->assertStringContainsString($ingredient->nama_bahan, $zip->getFromName('bahan_baku.csv'));
            $this->assertStringContainsString($table->code, $zip->getFromName('meja.csv'));
            $this->assertSame(1, $manifest['row_counts']['riwayat_meja']);
            $this->assertSame('proof-image', $zip->getFromName('berkas/bukti_bayar/'.$paid->id.'-uji.png'));
            $this->assertSame('product-image', $zip->getFromName('berkas/produk/'.$product->id.'-uji.png'));
            $this->assertSame('qris-image', $zip->getFromName('berkas/qris/toko-uji.png'));
            $this->assertNotFalse($zip->locateName('berkas/latar_denah/toko-cikscoffee-floor-plan.png'));
            foreach (['akun.csv', 'pesanan.csv', 'pengaturan.csv', 'manifest.json'] as $name) {
                $content = $zip->getFromName($name);
                foreach (['password', 'remember_token', 'checkout_key', 'payment_proof_hash', 'api_secret', 'must-not-export-this-secret'] as $secret) {
                    $this->assertStringNotContainsString($secret, $content);
                }
            }
            $this->assertDatabaseHas('ingredients', ['id' => $ingredient->id, 'stok' => 100]);
            $this->assertDatabaseHas('orders', ['id' => $waiting->id, 'status' => 'selesai']);
        } finally {
            $zip->close();
        }
    }

    public function test_excel_has_all_sections_with_numeric_prices_and_explicit_text_in_every_untrusted_column(): void
    {
        [$owner, $product] = $this->fixtures();
        $response = $this->actingAs($owner)->get(route('pemilik.exports.excel'))->assertOk();
        $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
        try {
            $this->assertCount(15, $book->getAllSheets());
            $sheet = $book->getSheetByName('Produk');
            $this->assertSame("'=Produk Uji", $sheet->getCell('D2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('D2')->getDataType());
            $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('F2')->getDataType());
            $this->assertEquals(15000, $sheet->getCell('F2')->getValue());
            $this->assertEquals($product->availableQuantity('base'), $sheet->getCell('J2')->getValue());
            $this->assertSame("'+628123456789", $book->getSheetByName('Pelanggan dan Staf')->getCell('D2')->getValue());
            $this->assertStringNotContainsString('must-not-export-this-secret', json_encode($book->getSheetByName('Pengaturan Toko')->toArray()));
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function test_filters_apply_to_orders_items_consumption_and_table_history_but_keep_current_master_data(): void
    {
        [$owner, $product, $paid] = $this->fixtures();
        $paid->forceFill(['created_at' => '2026-10-01 14:00:00'])->saveQuietly();
        TableStatusHistory::query()->update(['created_at' => '2026-10-01 14:00:00']);
        $file = app(SystemExportService::class)->generate('csv', ['date_from' => '2026-11-01', 'date_to' => '2026-11-30', 'payment_method' => 'cash']);
        $zip = new ZipArchive;
        $zip->open(Storage::disk('local')->path($file['path']));
        try {
            $manifest = json_decode($zip->getFromName('manifest.json'), true);
            foreach (['pesanan', 'transaksi', 'item_pesanan', 'pemakaian_bahan', 'riwayat_meja'] as $section) {
                $this->assertSame(0, $manifest['row_counts'][$section]);
            }
            $this->assertSame(2, $manifest['row_counts']['produk']);
            $this->assertStringContainsString($product->name, $zip->getFromName('produk.csv'));
            $this->assertFalse($zip->locateName('berkas/bukti_bayar/'.$paid->id.'-uji.png'));
        } finally {
            $zip->close();
        }
    }

    public function test_complete_csv_export_keeps_every_order_across_multiple_query_chunks(): void
    {
        [$owner] = $this->fixtures();
        $rows = [];
        for ($i = 1; $i <= 270; $i++) {
            $rows[] = [
                'order_number' => 'CK-BULK-'.$i, 'customer_name' => 'Pelanggan POS', 'user_id' => $owner->id,
                'cashier_id' => $owner->id, 'payment_method' => 'cash', 'total' => 1000,
                'status' => 'selesai', 'paid_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ];
        }
        DB::table('orders')->insert($rows);
        $file = app(SystemExportService::class)->generate('csv');
        $zip = new ZipArchive;
        $zip->open(Storage::disk('local')->path($file['path']));
        try {
            $orders = array_slice($this->csvRows($zip->getFromName('pesanan.csv')), 1);
            $transactions = array_slice($this->csvRows($zip->getFromName('transaksi.csv')), 1);
            $this->assertCount(272, $orders);
            $this->assertCount(272, array_unique(array_column($orders, 0)));
            $this->assertCount(271, $transactions);
            $this->assertContains('CK-BULK-270', array_column($orders, 1));
        } finally {
            $zip->close();
        }
    }

    public function test_missing_or_unsafe_media_is_reported_without_reading_files_outside_business_directories(): void
    {
        [$owner, $product] = $this->fixtures();
        Storage::disk('public')->put('private-secret.png', 'must-not-export-this-secret');
        $product->update(['image' => 'products/../private-secret.png']);
        Product::where('id', '!=', $product->id)->update(['image' => 'products/missing.png']);
        $response = $this->actingAs($owner)->get(route('pemilik.exports.csv'))->assertOk();
        $zip = new ZipArchive;
        $zip->open($response->baseResponse->getFile()->getPathname());
        try {
            $this->assertStringContainsString('Tidak ditemukan', $zip->getFromName('berkas.csv'));
            $this->assertFalse($zip->locateName('berkas/produk/'.$product->id.'-private-secret.png'));
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $this->assertStringNotContainsString('must-not-export-this-secret', $zip->getFromIndex($i));
            }
        } finally {
            $zip->close();
        }
    }

    public function test_pdf_is_a_real_document_and_oversized_documents_fail_without_truncation_or_leftover_files(): void
    {
        [$owner] = $this->fixtures();
        $response = $this->actingAs($owner)->get(route('pemilik.exports.pdf'))->assertOk();
        $this->assertStringStartsWith('%PDF-', file_get_contents($response->baseResponse->getFile()->getPathname()));
        $before = Storage::disk('local')->files('exports');
        config(['exports.pdf_max_rows' => 1, 'exports.excel_max_rows' => 1]);
        foreach (['pdf', 'excel'] as $format) {
            $this->getJson(route('pemilik.exports.'.$format))->assertUnprocessable()->assertJsonValidationErrors('format');
        }
        $this->assertSame($before, Storage::disk('local')->files('exports'));
    }

    public function test_exports_reject_invalid_filters_and_are_only_accessible_by_active_owners(): void
    {
        [$owner] = $this->fixtures();
        $this->actingAs($owner)->get(route('pemilik.exports.index'))->assertOk()->assertSeeText('Seluruh data penting')
            ->assertDontSeeText('Jadwal Ekspor Otomatis')->assertDontSeeText('Hasil Ekspor Otomatis');
        foreach (['csv', 'excel', 'pdf'] as $format) {
            foreach ([['scope' => 'invalid'], ['payment_method' => 'invalid'], ['date_from' => '2026-11-30', 'date_to' => '2026-11-01']] as $filters) {
                $this->getJson(route('pemilik.exports.'.$format, $filters))->assertUnprocessable();
            }
        }
        foreach (['karyawan', 'pengguna'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'is_active' => true]));
            $this->get(route('pemilik.exports.index'))->assertForbidden();
            $this->get(route('pemilik.exports.csv'))->assertForbidden();
        }
        $owner->update(['is_active' => false]);
        $this->actingAs($owner)->get(route('pemilik.exports.excel'))->assertForbidden();
    }

    private function csvRows(string $contents): array
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, ltrim($contents, "\xEF\xBB\xBF"));
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream, null, ',', '"', '')) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }

    private function fixtures(): array
    {
        $owner = User::factory()->create(['role' => 'pemilik', 'is_active' => true, 'phone' => '+628123456789']);
        $customer = User::factory()->create(['role' => 'pengguna', 'is_active' => true]);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee', 'is_active' => true]);
        $ingredient = Ingredient::create(['nama_bahan' => 'Biji Kopi Uji', 'stok' => 100, 'satuan' => 'gram']);
        $product = Product::create(['name' => '=Produk Uji', 'category_id' => $category->id, 'price' => 15000, 'price_lite' => 10000, 'is_active' => true, 'image' => 'products/uji.png']);
        $product->ingredients()->attach($ingredient->id, ['variant' => 'base', 'quantity' => 20]);
        $product->ingredients()->attach($ingredient->id, ['variant' => 'lite', 'quantity' => 10]);
        Product::create(['name' => 'Produk nonaktif', 'category_id' => $category->id, 'price' => 5000, 'is_active' => false]);
        $paid = Order::withoutEvents(fn () => Order::create([
            'order_number' => 'CK-EXPORT-PAID', 'customer_name' => '=Pelanggan', 'user_id' => $customer->id,
            'cashier_id' => $owner->id, 'payment_method' => 'qris', 'total' => 15000, 'status' => 'diambil',
            'paid_at' => now(), 'payment_proof' => 'payment_proofs/uji.png',
        ]));
        $item = $paid->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'variant' => 'base', 'quantity' => 1, 'price' => 15000, 'subtotal' => 15000]);
        DB::table('order_item_ingredients')->insert(['order_item_id' => $item->id, 'ingredient_id' => $ingredient->id, 'quantity' => 20]);
        $waiting = Order::withoutEvents(fn () => Order::create([
            'order_number' => 'CK-EXPORT-WAITING', 'customer_name' => 'Menunggu ambil', 'user_id' => $customer->id,
            'cashier_id' => $owner->id, 'payment_method' => 'qris', 'total' => 99999, 'status' => 'selesai', 'paid_at' => now(),
        ]));
        $layout = FloorLayout::create(['name' => 'Denah Uji', 'slug' => 'denah-uji', 'canvas_width' => 3508, 'canvas_height' => 2481, 'background_config' => ['elements' => [['type' => 'text', 'text' => 'Kasir']]], 'is_active' => true]);
        $table = CoffeeTable::create(['floor_layout_id' => $layout->id, 'code' => 'IN-01', 'name' => 'Meja Indoor', 'capacity' => 4, 'shape' => 'square', 'position_x' => 20, 'position_y' => 30, 'status' => 'occupied', 'is_active' => true]);
        TableStatusHistory::create(['coffee_table_id' => $table->id, 'user_id' => $owner->id, 'old_status' => 'available', 'new_status' => 'occupied', 'source' => 'owner_web']);
        ShopSetting::setValue('qris_image', 'qris/uji.png');
        ShopSetting::setValue('api_secret', 'must-not-export-this-secret');
        Storage::disk('public')->put('products/uji.png', 'product-image');
        Storage::disk('public')->put('qris/uji.png', 'qris-image');
        Storage::disk('local')->put('payment_proofs/uji.png', 'proof-image');

        return [$owner, $product, $paid, $waiting, $ingredient, $table];
    }
}
