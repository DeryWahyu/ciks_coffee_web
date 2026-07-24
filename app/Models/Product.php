<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string|null $description
 * @property string|null $image
 * @property string $price
 * @property string|null $price_lite
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $formatted_price
 * @property-read string|null $formatted_price_lite
 * @property-read \App\Models\Category|null $category
 */
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
     * Get ingredients for a specific variant with a pessimistic lock.
     *
     * Must be called inside a DB::transaction() so the lock is held
     * until commit, preventing concurrent reads from passing a stale
     * stock check.
     */
    public function ingredientsByVariantLocked(?string $variant)
    {
        return $this->ingredients()->wherePivot('variant', $variant)->lockForUpdate()->get();
    }

    /**
     * Cek ketersediaan produk berdasarkan stok bahan baku.
     *
     * Produk dianggap tersedia bila untuk setiap varian yang dijual
     * (coffee: base selalu, lite bila ada price_lite; non-coffee: default)
     * seluruh bahannya cukup untuk 1 porsi. Variabel $reason akan berisi
     * keterangan bahan yang kurang (untuk pesan ke klien).
     */
    public function isAvailable(?string &$reason = null): bool
    {
        $variantsToCheck = [];
        $isCoffee = $this->category && $this->category->isCoffee();

        if ($isCoffee) {
            $variantsToCheck[] = 'base';
            if ($this->price_lite !== null) {
                $variantsToCheck[] = 'lite';
            }
        } else {
            $variantsToCheck[] = null;
        }

        foreach ($variantsToCheck as $variant) {
            $ingredients = $this->ingredientsByVariant($variant);
            foreach ($ingredients as $ingredient) {
                if ($ingredient->stok < $ingredient->pivot->quantity) {
                    $variantLabel = $variant ? ' (' . ucfirst($variant) . ')' : '';
                    $reason = "Bahan {$ingredient->nama_bahan} kurang untuk {$this->name}{$variantLabel}.";
                    return false;
                }
            }
        }

        return true;
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
            ? 'Rp ' . number_format((float) $this->price_lite, 0, ',', '.')
            : null;
    }
}
