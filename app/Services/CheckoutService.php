<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Support\Decimal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /** Caller holds a transaction until the order, recipes, and stock commit. */
    public function prepare(array $items): array
    {
        $products = Product::query()
            ->with(['category' => fn ($query) => $query->orderBy('id')->lockForUpdate()])
            ->whereIn('id', array_column($items, 'product_id'))
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $total = 0;
        $prepared = [];
        $requirements = [];

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            if (! $product || ! $product->is_active || ! $product->category?->is_active) {
                $this->invalid('Produk atau kategori yang dipilih sudah tidak tersedia.');
            }
            $variant = $item['variant'] ?? null;
            if ($product->category->isCoffee()) {
                if (! in_array($variant, ['base', 'lite'], true)) {
                    $this->invalid("Varian Base atau Lite wajib dipilih untuk produk {$product->name}.");
                }
                if ($variant === 'lite' && $product->price_lite === null) {
                    $this->invalid("Varian Lite tidak tersedia untuk produk {$product->name}.");
                }
            } elseif ($variant !== null) {
                $this->invalid("Produk {$product->name} tidak memiliki varian.");
            }

            $quantity = (int) $item['quantity'];
            $price = Decimal::units($variant === 'lite' ? $product->price_lite : $product->price);
            $subtotal = $this->multiply($price, $quantity);
            if ($total > Decimal::MAX_UNITS - $subtotal) {
                $this->invalid('Total pesanan melebihi batas nominal yang didukung.');
            }
            $total += $subtotal;
            $recipe = $this->recipe($product, $variant);
            $this->addRequirements($requirements, $recipe, $quantity);
            $prepared[] = [
                'product_id' => $product->id,
                'product_name' => $product->name.($variant ? ' ('.ucfirst($variant).')' : ''),
                'variant' => $variant, 'quantity' => $quantity,
                'price' => Decimal::value($price), 'subtotal' => Decimal::value($subtotal),
                'recipe' => $recipe,
            ];
        }

        return ['items' => $prepared, 'total_units' => $total, 'requirements' => $requirements];
    }

    public function lockIngredients(array $requirements): Collection
    {
        $ingredients = Ingredient::query()->whereIn('id', array_keys($requirements))
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        foreach ($requirements as $id => $required) {
            $ingredient = $ingredients->get($id);
            if (! $ingredient || Decimal::units($ingredient->stok) < $required) {
                $this->invalid('Stok bahan '.($ingredient?->nama_bahan ?? 'yang dipilih').' tidak cukup untuk seluruh pesanan.');
            }
        }

        return $ingredients;
    }

    public function deduct(Collection $ingredients, array $requirements): void
    {
        foreach ($requirements as $id => $required) {
            $ingredient = $ingredients->get($id);
            $ingredient->update(['stok' => Decimal::value(Decimal::units($ingredient->stok) - $required)]);
        }
    }

    public function storeItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $recipe = $item['recipe'];
            unset($item['recipe']);
            $orderItem = $order->items()->create($item);
            foreach ($recipe as $ingredientId => $quantity) {
                $orderItem->ingredients()->attach($ingredientId, ['quantity' => Decimal::value($quantity)]);
            }
        }
    }

    /** Pending QRIS uses the recipe sold, even when the catalogue is edited. */
    public function requirementsForOrder(Order $order): array
    {
        $order->load('items.ingredients');
        $requirements = [];
        // Product locks are needed only when recovering an older, unsnapshotted order.
        $legacyProducts = Product::query()->whereIn('id', $order->items
            ->filter(fn ($item) => $item->ingredients->isEmpty())->pluck('product_id'))
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        if ($order->items->isEmpty()) {
            $this->invalid('Pesanan tidak memiliki item yang dapat diproses.');
        }
        foreach ($order->items as $item) {
            if ($item->ingredients->isEmpty()) {
                $product = $legacyProducts->get($item->product_id);
                if (! $product) {
                    $this->invalid("Produk untuk item {$item->product_name} tidak ditemukan.");
                }
                $recipe = $this->recipe($product, $item->variant);
                foreach ($recipe as $id => $quantity) {
                    $item->ingredients()->attach($id, ['quantity' => Decimal::value($quantity)]);
                }
            } else {
                $recipe = $item->ingredients->mapWithKeys(fn ($ingredient) => [
                    $ingredient->id => Decimal::units($ingredient->pivot->quantity),
                ])->all();
            }
            $this->addRequirements($requirements, $recipe, $item->quantity);
        }

        return $requirements;
    }

    private function recipe(Product $product, ?string $variant): array
    {
        $ingredients = $product->ingredientsByVariant($variant);
        if ($ingredients->isEmpty()) {
            $this->invalid("Produk {$product->name} belum memiliki resep yang valid.");
        }
        $recipe = [];
        foreach ($ingredients as $ingredient) {
            $units = Decimal::units($ingredient->pivot->quantity);
            if ($units <= 0 || ($recipe[$ingredient->id] ?? 0) + $units > 9999999999) {
                $this->invalid("Takaran bahan untuk {$product->name} tidak valid.");
            }
            $recipe[$ingredient->id] = ($recipe[$ingredient->id] ?? 0) + $units;
        }

        return $recipe;
    }

    private function addRequirements(array &$requirements, array $recipe, int $quantity): void
    {
        foreach ($recipe as $id => $units) {
            $required = $this->multiply($units, $quantity);
            if (($requirements[$id] ?? 0) > Decimal::MAX_UNITS - $required) {
                $this->invalid('Kebutuhan bahan pesanan melebihi batas stok yang didukung.');
            }
            $requirements[$id] = ($requirements[$id] ?? 0) + $required;
        }
    }

    private function multiply(int $units, int $quantity): int
    {
        if ($units < 0 || $quantity < 1 || ($units > 0 && $quantity > intdiv(Decimal::MAX_UNITS, $units))) {
            $this->invalid('Nominal atau jumlah pesanan melebihi batas yang didukung.');
        }

        return $units * $quantity;
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['items' => $message]);
    }
}
