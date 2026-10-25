<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('checkout_key')->nullable();
            $table->char('checkout_fingerprint', 64)->nullable();
            $table->unique(['user_id', 'checkout_key']);
        });
    }

    public function down(): void
    {
        // InnoDB may replace the implicit FK index with this composite index.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) && ! Schema::hasIndex('orders', ['user_id'])) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('user_id');
            });
        }
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'checkout_key']);
            $table->dropColumn(['checkout_key', 'checkout_fingerprint']);
        });
    }
};
