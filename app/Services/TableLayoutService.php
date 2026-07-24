<?php

namespace App\Services;

use App\Exceptions\TableVersionConflictException;
use App\Models\CoffeeTable;
use App\Models\FloorLayout;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TableLayoutService
{
    /**
     * Persist a layout edit and all supplied table coordinates atomically.
     *
     * @param array<string, mixed> $layoutAttributes
     * @param array<int, array<string, mixed>> $tablePositions
     *
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     * @throws TableVersionConflictException
     */
    public function updateLayout(
        FloorLayout $floorLayout,
        User $actor,
        array $layoutAttributes,
        array $tablePositions,
    ): FloorLayout {
        $this->assertActorCanManage($actor);

        if ($tablePositions === []) {
            throw new InvalidArgumentException('Minimal satu posisi meja harus dikirim.');
        }

        return DB::transaction(function () use ($floorLayout, $layoutAttributes, $tablePositions): FloorLayout {
            $lockedLayout = FloorLayout::query()
                ->whereKey($floorLayout->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $positionById = [];
            foreach ($tablePositions as $tablePosition) {
                $tableId = (int) ($tablePosition['id'] ?? 0);

                if ($tableId <= 0 || isset($positionById[$tableId])) {
                    throw new InvalidArgumentException('Data posisi meja tidak valid.');
                }

                $this->assertGeometry($tablePosition);
                $positionById[$tableId] = $tablePosition;
            }

            $lockedTables = CoffeeTable::query()
                ->where('floor_layout_id', $lockedLayout->id)
                ->whereIn('id', array_keys($positionById))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($lockedTables->count() !== count($positionById)) {
                throw new InvalidArgumentException('Satu atau lebih meja tidak berada pada denah ini.');
            }

            foreach ($lockedTables as $tableId => $lockedTable) {
                $expectedVersion = (int) $positionById[$tableId]['version'];

                if ($lockedTable->version !== $expectedVersion) {
                    throw new TableVersionConflictException($lockedTable);
                }
            }

            $layoutChanges = Arr::only($layoutAttributes, [
                'name',
                'description',
                'canvas_width',
                'canvas_height',
                'background_config',
            ]);

            if ($layoutChanges !== []) {
                $lockedLayout->fill($layoutChanges);

                if ($lockedLayout->isDirty()) {
                    $lockedLayout->save();
                }
            }

            foreach ($lockedTables as $tableId => $lockedTable) {
                $position = $positionById[$tableId];
                $lockedTable->fill([
                    'position_x' => $position['position_x'],
                    'position_y' => $position['position_y'],
                    'width' => $position['width'],
                    'height' => $position['height'],
                    'rotation' => $position['rotation'] ?? 0,
                ]);

                if ($lockedTable->isDirty()) {
                    $lockedTable->version++;
                    $lockedTable->save();
                }
            }

            return $lockedLayout->fresh(['coffeeTables.statusUpdatedBy']);
        }, 3);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function createTable(FloorLayout $floorLayout, User $actor, array $attributes): CoffeeTable
    {
        $this->assertActorCanManage($actor);
        $this->assertGeometry($attributes);

        return DB::transaction(function () use ($floorLayout, $attributes): CoffeeTable {
            $lockedLayout = FloorLayout::query()
                ->whereKey($floorLayout->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $table = $lockedLayout->coffeeTables()->create([
                'code' => Str::upper(trim((string) $attributes['code'])),
                'name' => trim((string) $attributes['name']),
                'capacity' => $attributes['capacity'],
                'shape' => $attributes['shape'],
                'position_x' => $attributes['position_x'],
                'position_y' => $attributes['position_y'],
                'width' => $attributes['width'],
                'height' => $attributes['height'],
                'rotation' => $attributes['rotation'] ?? 0,
                'is_active' => $attributes['is_active'] ?? true,
            ]);

            // MySQL applies status/version defaults at insert time, but those
            // values are not hydrated on the in-memory model automatically.
            // Reload before presenting the response to the owner UI.
            return $table->fresh(['floorLayout', 'statusUpdatedBy']);
        }, 3);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     * @throws TableVersionConflictException
     */
    public function updateTable(
        CoffeeTable $coffeeTable,
        User $actor,
        array $attributes,
        int $expectedVersion,
    ): CoffeeTable {
        $this->assertActorCanManage($actor);
        $this->assertGeometry($attributes);

        return DB::transaction(function () use ($coffeeTable, $attributes, $expectedVersion): CoffeeTable {
            $lockedTable = CoffeeTable::query()
                ->whereKey($coffeeTable->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTable->version !== $expectedVersion) {
                throw new TableVersionConflictException($lockedTable);
            }

            $lockedTable->fill([
                'code' => Str::upper(trim((string) $attributes['code'])),
                'name' => trim((string) $attributes['name']),
                'capacity' => $attributes['capacity'],
                'shape' => $attributes['shape'],
                'position_x' => $attributes['position_x'],
                'position_y' => $attributes['position_y'],
                'width' => $attributes['width'],
                'height' => $attributes['height'],
                'rotation' => $attributes['rotation'] ?? 0,
                'is_active' => $attributes['is_active'],
            ]);

            if ($lockedTable->isDirty()) {
                $lockedTable->version++;
                $lockedTable->save();
            }

            return $lockedTable->fresh(['floorLayout', 'statusUpdatedBy']);
        }, 3);
    }

    /**
     * @throws AuthorizationException
     * @throws TableVersionConflictException
     */
    public function updateActiveState(
        CoffeeTable $coffeeTable,
        User $actor,
        bool $isActive,
        int $expectedVersion,
    ): CoffeeTable {
        $this->assertActorCanManage($actor);

        return DB::transaction(function () use ($coffeeTable, $isActive, $expectedVersion): CoffeeTable {
            $lockedTable = CoffeeTable::query()
                ->whereKey($coffeeTable->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTable->version !== $expectedVersion) {
                throw new TableVersionConflictException($lockedTable);
            }

            if ($lockedTable->is_active !== $isActive) {
                $lockedTable->forceFill([
                    'is_active' => $isActive,
                    'version' => $lockedTable->version + 1,
                ])->save();
            }

            return $lockedTable->fresh(['floorLayout', 'statusUpdatedBy']);
        }, 3);
    }

    /**
     * @param array<string, mixed> $geometry
     *
     * @throws InvalidArgumentException
     */
    private function assertGeometry(array $geometry): void
    {
        $positionX = $this->toFloat($geometry, 'position_x');
        $positionY = $this->toFloat($geometry, 'position_y');
        $width = $this->toFloat($geometry, 'width');
        $height = $this->toFloat($geometry, 'height');
        $rotation = isset($geometry['rotation']) ? (float) $geometry['rotation'] : 0.0;

        if ($positionX < 0 || $positionY < 0 || $width <= 0 || $height <= 0
            || $positionX > 100 || $positionY > 100 || $width > 100 || $height > 100
            || $positionX + $width > 100 || $positionY + $height > 100) {
            throw new InvalidArgumentException('Posisi atau ukuran meja berada di luar batas denah.');
        }

        if ($rotation < 0 || $rotation > 359.99) {
            throw new InvalidArgumentException('Rotasi meja harus berada di antara 0 dan 359,99 derajat.');
        }
    }

    /**
     * @param array<string, mixed> $geometry
     */
    private function toFloat(array $geometry, string $key): float
    {
        if (! isset($geometry[$key]) || ! is_numeric($geometry[$key])) {
            throw new InvalidArgumentException('Data posisi meja tidak lengkap.');
        }

        return (float) $geometry[$key];
    }

    /**
     * @throws AuthorizationException
     */
    private function assertActorCanManage(User $actor): void
    {
        if (! $actor->is_active || ! $actor->isPemilik()) {
            throw new AuthorizationException('Anda tidak memiliki izin untuk mengatur denah meja.');
        }
    }
}
