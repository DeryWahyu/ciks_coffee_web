<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderReplayMigrationRollbackTest extends TestCase
{
    use DatabaseMigrations;

    public function test_replay_migrations_roll_back_and_reapply_without_losing_orders_or_the_owner_foreign_key(): void
    {
        $owner = User::factory()->create(['role' => 'karyawan', 'is_active' => true]);
        $order = Order::withoutEvents(fn () => Order::create([
            'user_id' => $owner->id, 'customer_name' => 'Rollback fixture',
            'order_number' => 'CK-ROLLBACK-OWNER', 'payment_method' => 'cash',
            'status' => 'antrian_baru', 'total' => 15000,
        ]));
        $checkout = require database_path('migrations/2026_10_25_000002_add_checkout_replay_to_orders_table.php');
        $proof = require database_path('migrations/2026_10_25_000004_add_payment_proof_hash_to_orders_table.php');

        try {
            $proof->down();
            $checkout->down();
            $this->assertFalse(Schema::hasColumn('orders', 'checkout_key'));
            $this->assertFalse(Schema::hasColumn('orders', 'payment_proof_hash'));
            $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $owner->id, 'total' => 15000]);
            try {
                DB::table('orders')->insert([
                    'user_id' => $owner->id + 999999, 'customer_name' => 'Invalid owner',
                    'order_number' => 'CK-INVALID-OWNER', 'payment_method' => 'cash',
                    'status' => 'antrian_baru', 'total' => 15000,
                ]);
                $this->fail('The owner foreign key must remain enforced after rollback.');
            } catch (QueryException $exception) {
                $this->assertSame('23000', (string) $exception->getCode());
            }
        } finally {
            if (! Schema::hasColumn('orders', 'checkout_key')) {
                $checkout->up();
            }
            if (! Schema::hasColumn('orders', 'payment_proof_hash')) {
                $proof->up();
            }
        }

        $this->assertTrue(Schema::hasIndex('orders', ['user_id', 'checkout_key'], 'unique'));
        $this->assertTrue(Schema::hasIndex('orders', ['user_id', 'payment_proof_hash'], 'unique'));
        $this->assertDatabaseCount('orders', 1);
    }
}
