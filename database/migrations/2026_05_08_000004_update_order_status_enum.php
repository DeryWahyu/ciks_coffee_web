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
        // Alter the enum column to include the new 'diambil' status.
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('antrian_baru', 'sedang_dibuat', 'selesai', 'diambil') DEFAULT 'antrian_baru'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to the original enum if needed.
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('antrian_baru', 'sedang_dibuat', 'selesai') DEFAULT 'antrian_baru'");
    }
};
