<?php

namespace Tests\Concerns;

use App\Models\CoffeeTable;
use App\Models\FloorLayout;
use App\Models\User;
use Illuminate\Support\Str;

trait CreatesTableAvailabilityFixtures
{
    /**
     * @param array<string, mixed> $attributes
     */
    protected function createFloorLayout(array $attributes = []): FloorLayout
    {
        return FloorLayout::query()->create(array_replace([
            'name' => 'Area Uji',
            'slug' => 'area-uji-' . Str::lower(Str::random(10)),
            'description' => 'Layout khusus pengujian.',
            'canvas_width' => 1200,
            'canvas_height' => 800,
            'background_config' => [
                'show_grid' => true,
                'elements' => [],
            ],
            'is_active' => true,
        ], $attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function createCoffeeTable(FloorLayout $layout, array $attributes = []): CoffeeTable
    {
        $sequence = CoffeeTable::query()->count() + 1;

        return CoffeeTable::query()->create(array_replace([
            'floor_layout_id' => $layout->id,
            'code' => 'M' . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
            'name' => 'Meja ' . $sequence,
            'capacity' => 4,
            'shape' => CoffeeTable::SHAPE_SQUARE,
            'position_x' => 10,
            'position_y' => 10,
            'width' => 15,
            'height' => 15,
            'rotation' => 0,
            'status' => CoffeeTable::STATUS_AVAILABLE,
            'is_active' => true,
            'version' => 1,
        ], $attributes));
    }

    protected function createTableUser(string $role = User::ROLE_PENGGUNA, bool $isActive = true): User
    {
        return User::factory()->create([
            'role' => $role,
            'is_active' => $isActive,
        ]);
    }
}
