<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MenuCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = require database_path('seeders/data/cikscoffee-menu.php');
        $assets = database_path('seeders/assets/products');
        $disk = Storage::disk('public');

        // Fail before writing records if a release lacks a generated photograph.
        foreach ($catalog['products'] as $item) {
            $file = $assets.'/'.$item['slug'].'.png';
            if (! is_file($file) || (@getimagesize($file)[2] ?? null) !== IMAGETYPE_PNG) {
                throw new RuntimeException('Gambar produk tidak tersedia atau tidak valid: '.$item['slug']);
            }
        }

        DB::transaction(function () use ($catalog, $assets, $disk): void {
            // Never reinterpret an existing stock quantity using a different unit.
            foreach ($catalog['ingredients'] as $item) {
                $existing = Ingredient::where('nama_bahan', $item['nama_bahan'])->lockForUpdate()->first();
                if ($existing && $existing->satuan !== $item['satuan']) {
                    throw new RuntimeException('Satuan bahan '.$item['nama_bahan'].' berbeda; samakan satuan secara manual sebelum menjalankan seeder.');
                }
            }

            $categories = [];
            foreach ($catalog['categories'] as $slug => $name) {
                $categories[$slug] = Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => true]);
            }

            $ingredients = [];
            foreach ($catalog['ingredients'] as $key => $item) {
                $ingredients[$key] = Ingredient::firstOrCreate(['nama_bahan' => $item['nama_bahan']], $item);
            }

            foreach ($catalog['products'] as $item) {
                $image = 'products/cikscoffee-menu-v1/'.$item['slug'].'.png';
                $product = Product::firstOrCreate([
                    'category_id' => $categories[$item['category']]->id,
                    'name' => $item['name'],
                ], [
                    'description' => $item['description'],
                    'price' => $item['price'],
                    'price_lite' => $item['price_lite'],
                    'image' => $image,
                    'is_active' => true,
                ]);

                // Existing products keep operator changes, stock use, and recipes.
                if ($product->wasRecentlyCreated) {
                    foreach ($item['recipes'] as $variant => $recipe) {
                        foreach ($recipe as $key => $quantity) {
                            $product->ingredients()->attach($ingredients[$key]->id, [
                                'variant' => $variant === 'default' ? null : $variant,
                                'quantity' => $quantity,
                            ]);
                        }
                    }
                }

                if ($product->image === $image && ! $disk->exists($image)) {
                    $stream = fopen($assets.'/'.$item['slug'].'.png', 'rb');
                    try {
                        if ($stream === false || ! $disk->put($image, $stream)) {
                            throw new RuntimeException('Gagal menyimpan gambar produk: '.$item['name']);
                        }
                    } finally {
                        if (is_resource($stream)) {
                            fclose($stream);
                        }
                    }
                }
            }
        }, 3);
    }
}
