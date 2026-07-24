<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class CoffeeTable extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_OCCUPIED = 'occupied';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_UNAVAILABLE = 'unavailable';

    public const SHAPE_ROUND = 'round';
    public const SHAPE_SQUARE = 'square';
    public const SHAPE_RECTANGLE = 'rectangle';

    protected $fillable = [
        'floor_layout_id',
        'code',
        'name',
        'capacity',
        'shape',
        'position_x',
        'position_y',
        'width',
        'height',
        'rotation',
        'status',
        'status_note',
        'is_active',
        'status_updated_by',
        'status_updated_at',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'position_x' => 'decimal:2',
            'position_y' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'rotation' => 'decimal:2',
            'is_active' => 'boolean',
            'status_updated_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    /**
     * The layout that owns this table.
     */
    public function floorLayout(): BelongsTo
    {
        return $this->belongsTo(FloorLayout::class);
    }

    /**
     * The user who last changed this table's availability.
     */
    public function statusUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_updated_by');
    }

    /**
     * Immutable audit records for status changes.
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(TableStatusHistory::class);
    }

    /**
     * Limit a query to tables visible in an active layout.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_AVAILABLE,
            self::STATUS_OCCUPIED,
            self::STATUS_RESERVED,
            self::STATUS_UNAVAILABLE,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function shapes(): array
    {
        return [
            self::SHAPE_ROUND,
            self::SHAPE_SQUARE,
            self::SHAPE_RECTANGLE,
        ];
    }

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::statuses(), true);
    }

    public static function isValidShape(string $shape): bool
    {
        return in_array($shape, self::shapes(), true);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_AVAILABLE => 'Tersedia',
            self::STATUS_OCCUPIED => 'Terisi',
            self::STATUS_RESERVED => 'Dipesan',
            self::STATUS_UNAVAILABLE => 'Tidak Tersedia',
            default => 'Tidak Diketahui',
        };
    }
}
