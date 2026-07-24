<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add payment_proof column
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_proof')->nullable()->after('change_amount');
        });

        // SQLite accepts the status as text; only MySQL requires an enum ALTER.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('menunggu_verifikasi', 'antrian_baru', 'sedang_dibuat', 'selesai', 'diambil') DEFAULT 'antrian_baru'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_proof');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('antrian_baru', 'sedang_dibuat', 'selesai', 'diambil') DEFAULT 'antrian_baru'");
        }
    }
};
