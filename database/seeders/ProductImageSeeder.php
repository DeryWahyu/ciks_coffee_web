<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        $imageMap = [
            // Coffee
            'Espresso'              => 'products/espresso.png',
            'Americano'             => 'products/americano.png',
            'Cappuccino'            => 'products/cappuccino.png',
            'Cafe Latte'            => 'products/cafe_latte.png',
            'Caramel Macchiato'     => 'products/caramel_macchiato.png',
            'Mocha Latte'           => 'products/mocha_latte.png',
            'Hazelnut Latte'        => 'products/hazelnut_latte.png',
            'Kopi Susu Gula Aren'   => 'products/kopi_susu_gula_aren.png',

            // Milk
            'Choco Milk'            => 'products/choco_milk.png',
            'Matcha Latte'          => 'products/matcha_latte.png',
            'Vanilla Milk'          => 'products/vanilla_milk.png',
            'Caramel Milk'          => 'products/caramel_milk.png',
            'Oat Milk Honey'        => 'products/oat_milk_honey.png',

            // Tea
            'Earl Grey Tea'         => 'products/earl_grey_tea.png',
            'Green Tea Latte'       => 'products/green_tea_latte.png',
            'Chamomile Tea'         => 'products/chamomile_tea.png',
            'Lychee Tea'            => 'products/lychee_tea.png',
            'Lemon Tea'             => 'products/lemon_tea.png',

            // Squash
            'Lemon Squash'          => 'products/lemon_squash.png',
            'Orange Squash'         => 'products/orange_squash.png',
            'Strawberry Squash'     => 'products/strawberry_squash.png',
            'Mango Squash'          => 'products/mango_squash.png',

            // Snack
            'Roti Bakar Cokelat'    => 'products/roti_bakar_cokelat.png',
            'Roti Bakar Keju'       => 'products/roti_bakar_keju.png',
            'French Fries'          => 'products/french_fries.png',
            'Pisang Goreng'         => 'products/pisang_goreng.png',
            'Sosis Bakar'           => 'products/sosis_bakar.png',

            // Meal
            'Nasi Goreng Spesial'   => 'products/nasi_goreng_spesial.png',
            'Chicken Sandwich'      => 'products/chicken_sandwich.png',
            'Nasi Ayam Geprek'      => 'products/nasi_ayam_geprek.png',
            'Omelette Keju'         => 'products/omelette_keju.png',
        ];

        foreach ($imageMap as $productName => $imagePath) {
            $updated = Product::where('name', $productName)
                ->whereNull('image')
                ->update(['image' => $imagePath]);

            if ($updated) {
                $this->command->info("  ✓ {$productName} → {$imagePath}");
            } else {
                // Product already has an image or not found
                $product = Product::where('name', $productName)->first();
                if ($product && $product->image) {
                    $this->command->warn("  ⊘ {$productName} already has image, skipped.");
                } else {
                    $this->command->error("  ✗ {$productName} not found!");
                }
            }
        }
    }
}
