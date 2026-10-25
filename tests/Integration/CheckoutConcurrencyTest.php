<?php

namespace Tests\Integration;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/** Real independent connections; SQLite cannot verify InnoDB row locking. */
class CheckoutConcurrencyTest extends TestCase
{
    private array $proofFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->proofFiles as $path) {
            Storage::disk('local')->delete($path);
        }
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires the isolated MySQL/MariaDB integration database.');
        }
        if (! str_starts_with(DB::connection()->getDatabaseName(), 'cikscoffee_audit_')) {
            $this->fail('Refusing to migrate a database without the cikscoffee_audit_ prefix.');
        }
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
    }

    public function test_two_cashiers_cannot_sell_the_same_last_portion(): void
    {
        [$product, $ingredient] = $this->product(1);
        $results = $this->race([
            [$this->user('karyawan'), '/karyawan/pos/checkout', $this->payload($product)],
            [$this->user('karyawan'), '/karyawan/pos/checkout', $this->payload($product)],
        ]);
        $this->assertEqualsCanonicalizing([200, 422], array_column($results, 'status'));
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame('0.00', $ingredient->fresh()->stok);
    }

    public function test_simultaneous_retries_of_one_payment_create_one_transaction(): void
    {
        [$product, $ingredient] = $this->product(1);
        $employee = $this->user('karyawan');
        $payload = $this->payload($product);
        $results = $this->race([
            [$employee, '/karyawan/pos/checkout', $payload],
            [$employee, '/karyawan/pos/checkout', $payload],
        ]);
        $this->assertSame([200, 200], array_column($results, 'status'));
        $this->assertSame($results[0]['body']['order']['id'], $results[1]['body']['order']['id']);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('order_item_ingredients', 1);
        $this->assertSame('0.00', $ingredient->fresh()->stok);
    }

    public function test_first_orders_of_a_day_have_distinct_numbers_even_without_shared_stock_locks(): void
    {
        [$first] = $this->product(10);
        [$second] = $this->product(10);
        $results = $this->race([
            [$this->user('karyawan'), '/karyawan/pos/checkout', $this->payload($first)],
            [$this->user('karyawan'), '/karyawan/pos/checkout', $this->payload($second)],
        ]);
        $this->assertSame([200, 200], array_column($results, 'status'));
        $prefix = 'CK-'.now()->format('Ymd').'-';
        $this->assertSame([$prefix.'0001', $prefix.'0002'], Order::orderBy('order_number')->pluck('order_number')->all());
    }

    public function test_pos_and_mobile_cannot_spend_the_same_last_ingredient(): void
    {
        [$product, $ingredient] = $this->product(1);
        $customer = $this->user('pengguna');
        $proof = 'payment_proofs/concurrency-'.Str::uuid().'.png';
        Storage::disk('local')->put($proof, 'private-test-proof');
        $this->proofFiles[] = $proof;
        $order = DB::transaction(function () use ($customer, $product, $proof) {
            $cart = app(CheckoutService::class)->prepare([['product_id' => $product->id, 'quantity' => 1]]);
            $order = Order::create(['order_number' => 'CK-PENDING-'.Str::uuid(), 'customer_name' => $customer->name,
                'user_id' => $customer->id, 'payment_method' => 'qris', 'payment_proof' => $proof,
                'total' => 15000, 'status' => 'menunggu_verifikasi']);
            app(CheckoutService::class)->storeItems($order, $cart['items']);

            return $order;
        });
        $results = $this->race([
            [$this->user('karyawan'), '/karyawan/pos/checkout', $this->payload($product)],
            [$this->user('karyawan'), '/karyawan/orders/'.$order->id.'/verify', [], 'PATCH'],
        ]);
        $this->assertContains($results[0]['status'], [200, 422]);
        $this->assertSame(302, $results[1]['status']);
        $this->assertNotSame($results[0]['status'] === 200, $results[1]['flash_success'] !== null);
        $this->assertSame(1, Order::where('status', 'antrian_baru')->count());
        $this->assertSame('0.00', $ingredient->fresh()->stok);
    }

    private function race(array $requests): array
    {
        $barrier = sys_get_temp_dir().'/cikscoffee-barrier-'.Str::uuid();
        mkdir($barrier, 0700);
        $connection = config('database.connections.mysql');
        $environment = [
            'APP_ENV' => 'testing', 'APP_KEY' => config('app.key'), 'APP_DEBUG' => 'false',
            'DB_CONNECTION' => 'mysql', 'DB_HOST' => $connection['host'],
            'DB_PORT' => (string) $connection['port'], 'DB_DATABASE' => $connection['database'],
            'DB_USERNAME' => $connection['username'], 'DB_PASSWORD' => $connection['password'] ?? '', 'DB_URL' => '',
            'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array', 'QUEUE_CONNECTION' => 'sync', 'BROADCAST_CONNECTION' => 'null',
        ];
        $processes = [];
        try {
            foreach ($requests as $i => $specification) {
                [$user, $path, $payload] = $specification;
                $method = $specification[3] ?? 'POST';
                $process = new Process([PHP_BINARY, base_path('tests/Support/checkout-worker.php'),
                    (string) $user->id, $path, base64_encode(json_encode($payload, JSON_THROW_ON_ERROR)), $barrier, (string) $i, $method,
                ], base_path(), $environment, timeout: 30);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 20;
            while (count(glob($barrier.'/ready-*')) !== count($requests)) {
                foreach ($processes as $process) {
                    if (! $process->isRunning()) {
                        $this->fail('Worker stopped before the barrier: '.$process->getErrorOutput().$process->getOutput());
                    }
                }
                if (microtime(true) > $deadline) {
                    $this->fail('Workers did not reach the checkout barrier.');
                }
                usleep(10000);
            }
            file_put_contents($barrier.'/go', 'go');
            $results = [];
            foreach ($processes as $process) {
                $this->assertSame(0, $process->wait(), $process->getErrorOutput());
                $results[] = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            }

            return $results;
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            foreach (glob($barrier.'/*') as $file) {
                unlink($file);
            }
            rmdir($barrier);
        }
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function payload(Product $product): array
    {
        return ['customer_name' => 'Pembeli uji', 'payment_method' => 'cash', 'cash_received' => 20000,
            'idempotency_key' => (string) Str::uuid(), 'expected_total' => 15000,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];
    }

    private function product(int $stock): array
    {
        $category = Category::firstOrCreate(['slug' => 'snack'], ['name' => 'Snack', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Produk uji', 'price' => 15000, 'is_active' => true]);
        $ingredient = Ingredient::create(['nama_bahan' => 'Bahan '.Str::uuid(), 'satuan' => 'gram', 'stok' => $stock]);
        $product->ingredients()->attach($ingredient->id, ['quantity' => 1, 'variant' => null]);

        return [$product, $ingredient];
    }
}
