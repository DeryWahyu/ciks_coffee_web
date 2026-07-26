<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class PemilikExportFormulaInjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_export_neutralizes_every_formula_prefix_in_customer_and_product_names(): void
    {
        $owner = $this->owner();
        $customer = $this->customer();
        $category = Category::create(['name' => 'Minuman', 'is_active' => true]);

        foreach (['=', '+', '-', '@'] as $prefix) {
            $this->makeCompletedOrder($owner, $customer, $category, "{$prefix}pelanggan", "{$prefix}produk");
        }

        $response = $this->actingAs($owner)->get(route('pemilik.exports.csv'));

        $response->assertOk();
        $rows = array_map(
            'str_getcsv',
            preg_split('/\r\n|\r|\n/', ltrim($response->streamedContent(), "\xEF\xBB\xBF"), -1, PREG_SPLIT_NO_EMPTY),
        );
        $customers = array_column(array_slice($rows, 1), 3);
        $items = array_column(array_slice($rows, 1), 6);

        foreach (['=', '+', '-', '@'] as $prefix) {
            $this->assertContains("'{$prefix}pelanggan", $customers);
            $this->assertContains("'{$prefix}produk x1", $items);
        }
    }

    public function test_xlsx_export_writes_formula_like_customer_and_product_names_as_text(): void
    {
        $owner = $this->owner();
        $customer = $this->customer();
        $category = Category::create(['name' => 'Minuman', 'is_active' => true]);
        $this->makeCompletedOrder($owner, $customer, $category, '=pelanggan', '@produk');

        $response = $this->actingAs($owner)->get(route('pemilik.exports.excel'));
        $response->assertOk();

        $temporaryFile = tempnam(sys_get_temp_dir(), 'ciks-export-');

        try {
            file_put_contents($temporaryFile, $response->streamedContent());
            $spreadsheet = IOFactory::load($temporaryFile);
            $sheet = $spreadsheet->getActiveSheet();

            $this->assertSame("'=pelanggan", $sheet->getCell('D2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('D2')->getDataType());
            $this->assertSame("'@produk x1", $sheet->getCell('G2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('G2')->getDataType());

            $spreadsheet->disconnectWorksheets();
        } finally {
            if (file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    private function owner(): User
    {
        return User::factory()->create([
            'role' => 'pemilik',
            'is_active' => true,
        ]);
    }

    private function customer(): User
    {
        return User::factory()->create([
            'role' => 'pengguna',
            'is_active' => true,
        ]);
    }

    private function makeCompletedOrder(
        User $owner,
        User $customer,
        Category $category,
        string $customerName,
        string $productName,
    ): void {
        $product = Product::create([
            'category_id' => $category->id,
            'name' => $productName,
            'price' => 15000,
            'is_active' => true,
        ]);
        $order = Order::create([
            'order_number' => 'CK-EXPORT-' . str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT),
            'customer_name' => $customerName,
            'user_id' => $customer->id,
            'cashier_id' => $owner->id,
            'payment_method' => 'cash',
            'total' => 15000,
            'status' => 'selesai',
            'paid_at' => now(),
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'price' => 15000,
            'subtotal' => 15000,
        ]);
    }
}
