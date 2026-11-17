<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ImageVariants;
use Illuminate\Console\Command;
use Throwable;

class OptimizeMedia extends Command
{
    protected $signature = 'media:optimize';

    protected $description = 'Siapkan gambar produk dan denah yang ringan tanpa mengganti gambar asli';

    public function handle(ImageVariants $images): int
    {
        $prepared = 0;
        $skipped = 0;
        foreach (Product::whereNotNull('image')->select('id', 'image')->lazyById(100) as $product) {
            try {
                $images->prepareProduct($product->image) ? $prepared++ : $skipped++;
            } catch (Throwable $exception) {
                report($exception);
                $this->error('Gambar produk '.$product->id.' gagal diproses.');

                return self::FAILURE;
            }
        }
        $images->prepareFloor();
        $this->info("{$prepared} gambar produk disiapkan; {$skipped} dilewati. Gambar asli tetap tersedia.");

        return self::SUCCESS;
    }
}
