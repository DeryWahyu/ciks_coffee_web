<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'image',
        'price',
        'price_lite',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'price_lite' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Category this product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * All ingredients (bahan baku) used in this product.
     */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'ingredient_product')
            ->withPivot('quantity', 'variant')
            ->withTimestamps();
    }

    /**
     * Get ingredients for a specific variant (base, lite, or null).
     */
    public function ingredientsByVariant(?string $variant)
    {
        return $this->ingredients()->wherePivot('variant', $variant)->get();
    }

    /**
     * Check if product has lite pricing (coffee only).
     */
    public function hasLitePrice(): bool
    {
        return $this->price_lite !== null;
    }

    /**
     * Get formatted base price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    /**
     * Get formatted lite price.
     */
    public function getFormattedPriceLiteAttribute(): ?string
    {
        return $this->price_lite
            ? 'Rp ' . number_format($this->price_lite, 0, ',', '.')
            : null;
    }
}
