<?php

namespace App\Models;

use App\Services\ReservedStock;
use App\Support\Decimal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    use HasFactory;

    public function scopeWithReservedStock($query)
    {
        return $query->addSelect('ingredients.*')->addSelect(['reserved_quantity' => ReservedStock::active()
            ->whereColumn('ingredient_id', 'ingredients.id')->selectRaw('COALESCE(SUM(quantity), 0)')]);
    }

    public function getAvailableStockAttribute(): string
    {
        return Decimal::value(max(0, Decimal::units($this->stok) - Decimal::units($this->reserved_quantity ?? 0)));
    }

    protected $fillable = ['nama_bahan', 'satuan', 'stok'];

    protected function casts(): array
    {
        return [
            'stok' => 'decimal:2',
        ];
    }

    /**
     * Products that use this ingredient.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'ingredient_product')
            ->withPivot('quantity', 'variant')
            ->withTimestamps();
    }
}
