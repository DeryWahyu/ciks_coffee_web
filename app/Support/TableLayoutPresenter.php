<?php

namespace App\Support;

use App\Models\CoffeeTable;
use App\Models\FloorLayout;
use Illuminate\Support\Collection;

class TableLayoutPresenter
{
    /**
     * Customer responses deliberately omit internal notes, actors, and inactive tables.
     *
     * @return array<string, mixed>
     */
    public static function forCustomer(FloorLayout $floorLayout): array
    {
        return self::present($floorLayout, false, false);
    }

    /**
     * Staff responses include the operational fields required by employee/owner tools.
     *
     * @return array<string, mixed>
     */
    public static function forStaff(FloorLayout $floorLayout, bool $includeInactive = false): array
    {
        return self::present($floorLayout, true, $includeInactive);
    }

    /**
     * @return array<string, mixed>
     */
    public static function table(CoffeeTable $coffeeTable, bool $includeOperationalFields = true): array
    {
        $data = [
            'id' => $coffeeTable->id,
            'code' => $coffeeTable->code,
            'name' => $coffeeTable->name,
            'capacity' => $coffeeTable->capacity,
            'shape' => $coffeeTable->shape,
            'position_x' => (float) $coffeeTable->position_x,
            'position_y' => (float) $coffeeTable->position_y,
            'width' => (float) $coffeeTable->width,
            'height' => (float) $coffeeTable->height,
            'rotation' => (float) $coffeeTable->rotation,
            'status' => $coffeeTable->status,
            'status_label' => $coffeeTable->status_label,
            'version' => $coffeeTable->version,
            'status_updated_at' => $coffeeTable->status_updated_at?->toIso8601String(),
        ];

        if (! $includeOperationalFields) {
            return $data;
        }

        $data['is_active'] = $coffeeTable->is_active;
        $data['status_note'] = $coffeeTable->status_note;
        $data['status_updated_by'] = $coffeeTable->statusUpdatedBy === null ? null : [
            'id' => $coffeeTable->statusUpdatedBy->id,
            'name' => $coffeeTable->statusUpdatedBy->name,
        ];

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private static function present(FloorLayout $floorLayout, bool $includeOperationalFields, bool $includeInactive): array
    {
        /** @var Collection<int, CoffeeTable> $tables */
        $tables = $floorLayout->relationLoaded('coffeeTables')
            ? $floorLayout->coffeeTables
            : $floorLayout->coffeeTables()->with('statusUpdatedBy')->orderBy('code')->get();

        $tables = $tables
            ->when(! $includeInactive, fn (Collection $items) => $items->where('is_active', true))
            ->sortBy('code')
            ->values();

        $summary = array_fill_keys(CoffeeTable::statuses(), 0);
        foreach ($tables as $table) {
            $summary[$table->status]++;
        }

        return [
            'layout' => array_filter([
                'id' => $floorLayout->id,
                'name' => $floorLayout->name,
                'description' => $floorLayout->description,
                'canvas_width' => $floorLayout->canvas_width,
                'canvas_height' => $floorLayout->canvas_height,
                'background_config' => $floorLayout->background_config,
                'is_active' => $includeOperationalFields ? $floorLayout->is_active : null,
                'updated_at' => $floorLayout->updated_at?->toIso8601String(),
            ], static fn (mixed $value): bool => $value !== null),
            'summary' => [
                'total' => $tables->count(),
                ...$summary,
            ],
            'tables' => $tables
                ->map(fn (CoffeeTable $table): array => self::table($table, $includeOperationalFields))
                ->all(),
        ];
    }
}
