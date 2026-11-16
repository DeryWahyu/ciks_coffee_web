<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Production does not run demo seeders. Start with an empty canvas only
        // when no layout exists; keep all existing layouts and their status.
        if (DB::table('floor_layouts')->exists()) {
            return;
        }

        DB::table('floor_layouts')->insert([
            'name' => 'Area Utama',
            'slug' => 'area-utama',
            'description' => 'Atur posisi dan tambahkan meja sesuai kondisi kedai.',
            'canvas_width' => 1200,
            'canvas_height' => 800,
            'background_config' => json_encode(['show_grid' => true, 'elements' => []], JSON_THROW_ON_ERROR),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Keep operational layouts and tables: the owner may have customized
        // this initial canvas since deployment. Schema migrations own deletion.
    }
};
