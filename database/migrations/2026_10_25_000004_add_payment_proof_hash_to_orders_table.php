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
            $table->char('payment_proof_hash', 64)->nullable();
            $table->unique(['user_id', 'payment_proof_hash']);
        });
    }

    public function down(): void
    {
        // Keep a child index for orders.user_id before removing either replay index.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) && ! Schema::hasIndex('orders', ['user_id'])) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('user_id');
            });
        }
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'payment_proof_hash']);
            $table->dropColumn('payment_proof_hash');
        });
    }
};
