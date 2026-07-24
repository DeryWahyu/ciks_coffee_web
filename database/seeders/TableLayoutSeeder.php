<?php

namespace Database\Seeders;

use App\Models\CoffeeTable;
use App\Models\FloorLayout;
use Illuminate\Database\Seeder;

class TableLayoutSeeder extends Seeder
{
    /**
     * Seed a configurable dummy layout without overwriting existing settings
     * or operational table statuses when the seeder is run again.
     */
    public function run(): void
    {
        $layout = FloorLayout::firstOrCreate(
            ['slug' => 'area-utama'],
            [
                'name' => 'Area Utama',
                'description' => 'Denah dummy Ciks Coffee. Posisi meja dapat diatur oleh pemilik pada editor layout.',
                'canvas_width' => 1200,
                'canvas_height' => 800,
                'background_config' => [
                    'theme' => 'ciks-coffee',
                    'show_grid' => true,
                    'elements' => [
                        ['type' => 'counter', 'label' => 'Kasir & Bar', 'position_x' => 50, 'position_y' => 5],
                        ['type' => 'window', 'label' => 'Jendela', 'position_x' => 96, 'position_y' => 40],
                        ['type' => 'entrance', 'label' => 'Pintu Masuk', 'position_x' => 50, 'position_y' => 96],
                    ],
                ],
                'is_active' => true,
            ],
        );

        foreach ($this->dummyTables() as $table) {
            CoffeeTable::firstOrCreate(
                [
                    'floor_layout_id' => $layout->id,
                    'code' => $table['code'],
                ],
                $table + [
                    'floor_layout_id' => $layout->id,
                    'status' => CoffeeTable::STATUS_AVAILABLE,
                    'is_active' => true,
                    'version' => 1,
                ],
            );
        }
    }

    /**
     * @return array<int, array<string, int|float|string>>
     */
    private function dummyTables(): array
    {
        return [
            ['code' => 'M01', 'name' => 'Meja 1', 'capacity' => 2, 'shape' => CoffeeTable::SHAPE_ROUND, 'position_x' => 12, 'position_y' => 17, 'width' => 13, 'height' => 13, 'rotation' => 0],
            ['code' => 'M02', 'name' => 'Meja 2', 'capacity' => 2, 'shape' => CoffeeTable::SHAPE_ROUND, 'position_x' => 39, 'position_y' => 17, 'width' => 13, 'height' => 13, 'rotation' => 0],
            ['code' => 'M03', 'name' => 'Meja 3', 'capacity' => 2, 'shape' => CoffeeTable::SHAPE_ROUND, 'position_x' => 66, 'position_y' => 17, 'width' => 13, 'height' => 13, 'rotation' => 0],
            ['code' => 'M04', 'name' => 'Meja 4', 'capacity' => 4, 'shape' => CoffeeTable::SHAPE_SQUARE, 'position_x' => 11, 'position_y' => 43, 'width' => 17, 'height' => 16, 'rotation' => 0],
            ['code' => 'M05', 'name' => 'Meja 5', 'capacity' => 4, 'shape' => CoffeeTable::SHAPE_SQUARE, 'position_x' => 41, 'position_y' => 43, 'width' => 17, 'height' => 16, 'rotation' => 0],
            ['code' => 'M06', 'name' => 'Meja 6', 'capacity' => 4, 'shape' => CoffeeTable::SHAPE_SQUARE, 'position_x' => 71, 'position_y' => 43, 'width' => 17, 'height' => 16, 'rotation' => 0],
            ['code' => 'M07', 'name' => 'Meja 7', 'capacity' => 6, 'shape' => CoffeeTable::SHAPE_RECTANGLE, 'position_x' => 10, 'position_y' => 70, 'width' => 28, 'height' => 14, 'rotation' => 0],
            ['code' => 'M08', 'name' => 'Meja 8', 'capacity' => 6, 'shape' => CoffeeTable::SHAPE_RECTANGLE, 'position_x' => 63, 'position_y' => 70, 'width' => 28, 'height' => 14, 'rotation' => 0],
            ['code' => 'M09', 'name' => 'Meja 9', 'capacity' => 2, 'shape' => CoffeeTable::SHAPE_ROUND, 'position_x' => 84, 'position_y' => 32, 'width' => 12, 'height' => 12, 'rotation' => 0],
            ['code' => 'M10', 'name' => 'Meja 10', 'capacity' => 4, 'shape' => CoffeeTable::SHAPE_SQUARE, 'position_x' => 42, 'position_y' => 70, 'width' => 16, 'height' => 14, 'rotation' => 0],
        ];
    }
}
