<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change the enum to include 'diambil' status
        // SQLite doesn't support ALTER COLUMN for enum, so we handle it via model validation
        // For MySQL, we would ALTER. Since the project uses SQLite, we just need the model to accept it.
        // The Order model status_label already uses a match() fallback, so we just need to ensure
        // the status column accepts the new value.

        // For SQLite: columns are not strictly typed, so 'diambil' will work without migration changes.
        // For MySQL environments, uncomment below:
        // DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('antrian_baru', 'sedang_dibuat', 'selesai', 'diambil') DEFAULT 'antrian_baru'");
    }

    public function down(): void
    {
        // Revert if needed
    }
};
