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
        // SQLite stores this as a flexible text affinity and cannot MODIFY a
        // column. MySQL needs the explicit enum alteration used in production.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('antrian_baru', 'sedang_dibuat', 'selesai', 'diambil') DEFAULT 'antrian_baru'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('antrian_baru', 'sedang_dibuat', 'selesai') DEFAULT 'antrian_baru'");
        }
    }
};
