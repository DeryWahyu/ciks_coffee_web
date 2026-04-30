<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    use HasFactory;

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
