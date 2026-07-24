<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FloorLayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'canvas_width',
        'canvas_height',
        'background_config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'canvas_width' => 'integer',
            'canvas_height' => 'integer',
            'background_config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Tables displayed on this layout.
     */
    public function coffeeTables(): HasMany
    {
        return $this->hasMany(CoffeeTable::class);
    }
}
