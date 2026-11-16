<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('floor_layouts')->where('canvas_width', 1200)->where('canvas_height', 800)
            ->orderBy('id')->get()->each(function (object $layout): void {
                $config = json_decode($layout->background_config ?? '{}', true, 512, JSON_THROW_ON_ERROR);
                if (! empty($config['elements']) || DB::table('coffee_tables')->where('floor_layout_id', $layout->id)->exists()) {
                    return;
                }

                $config['show_grid'] = false;
                DB::table('floor_layouts')->where('id', $layout->id)->update([
                    'canvas_width' => 3508,
                    'canvas_height' => 2481,
                    'background_config' => json_encode($config, JSON_THROW_ON_ERROR),
                ]);
            });
    }

    public function down(): void
    {
        // Keep the owner's saved canvas dimensions and geometry when rolling back.
    }
};
