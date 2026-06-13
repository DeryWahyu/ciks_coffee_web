<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class TenProductsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'slug');

        $products = [
            [
                'name' => 'Espresso',
                'category_id' => $categories['coffee'],
                'description' => 'Espresso premium dengan biji kopi pilihan.',
                'price' => 15000,
                'price_lite' => 12000,
                'image' => 'products/espresso.jpg',
                'is_active' => true,
                'ingredients' => [
                    'base' => [1 => 18], // Biji Kopi Espresso
                    'lite' => [1 => 12],
                ]
            ],
            [
                'name' => 'Cappuccino',
                'category_id' => $categories['coffee'],
                'description' => 'Espresso dengan susu UHT hangat dan busa lembut.',
                'price' => 22000,
                'price_lite' => 18000,
                'image' => 'products/cappuccino.jpg',
                'is_active' => true,
                'ingredients' => [
                    'base' => [1 => 18, 2 => 150], // Biji Kopi Espresso, Susu UHT
                    'lite' => [1 => 12, 2 => 100],
                ]
            ],
            [
                'name' => 'Kopi Susu Gula Aren',
                'category_id' => $categories['coffee'],
                'description' => 'Es kopi susu kekinian dengan rasa manis gula aren asli.',
                'price' => 18000,
                'price_lite' => 15000,
                'image' => 'products/kopi_susu_aren.jpg',
                'is_active' => true,
                'ingredients' => [
                    'base' => [1 => 18, 2 => 120],
                    'lite' => [1 => 12, 2 => 80],
                ]
            ],
            [
                'name' => 'Matcha Latte',
                'category_id' => $categories['milk'],
                'description' => 'Minuman susu krim dengan bubuk matcha Jepang berkualitas tinggi.',
                'price' => 20000,
                'price_lite' => null,
                'image' => 'products/matcha_latte.jpg',
                'is_active' => true,
                'ingredients' => [
                    'base' => [2 => 200], // Susu UHT
                ]
            ],
            [
                'name' => 'Chocolate Milkshake',
                'category_id' => $categories['milk'],
                'description' => 'Susu cokelat segar diblender dengan es krim cokelat lembut.',
                'price' => 22000,
                'price_lite' => null,
                'image' => 'products/chocolate_milkshake.jpg',
                'is_active' => true,
                'ingredients' => [
                    'base' => [2 => 200],
                ]
            ],
            [
                'name' => 'French Fries',
                'category_id' => $categories['snack'],
                'description' => 'Kentang goreng renyah disajikan dengan saus sambal dan mayones.',
                'price' => 15000,
                'price_lite' => null,
                'image' => 'products/french_fries.jpg',
                'is_active' => true,
                'ingredients' => [
                    'base' => [3 => 1, 4 => 2, 5 => 100], // Kentang Frozen, Garam, Minyak Goreng
                ]
            ],
            [
                'name' => 'Croissant Chocolate',
                'category_id' => $categories['snack'],
                'description' => 'Pastry khas Perancis yang renyah dengan isian cokelat melimpah.',
                'price' => 18000,
                'price_lite' => null,
                'image' => 'products/croissant.jpg',
                'is_active' => true,
                'ingredients' => [
                    'base' => [4 => 1],
                ]
            ],
            [
                'name' => 'Lemon Squash',
                'category_id' => $categories['squash'],
                'description' => 'Minuman lemon segar bersoda yang melepas dahaga.',
                'price' => 16000,
                'price_lite' => null,
                'image' => 'products/lemon_squash.jpg',
                'is_active' => true,
                'ingredients' => [
                    'base' => [4 => 2],
                ]
            ],
            [
                'name' => 'Ice Lychee Tea',
                'category_id' => $categories['tea'],
                'description' => 'Teh manis dingin dengan buah leci segar.',
                'price' => 15000,
                'price_lite' => null,
                'image' => 'products/lychee_tea.jpg',
                'is_active' => true,
                'ingredients' => [
                    'base' => [4 => 1],
                ]
            ],
            [
                'name' => 'Nasi Goreng Ciks',
                'category_id' => $categories['meal'],
                'description' => 'Nasi goreng khas Ciks Coffee dengan telur mata sapi dan kerupuk.',
                'price' => 25000,
                'price_lite' => null,
                'image' => 'products/nasi_goreng.jpg',
                'is_active' => true,
                'ingredients' => [
                    'base' => [4 => 2, 5 => 20], // Garam, Minyak Goreng
                ]
            ],
        ];

        foreach ($products as $data) {
            $product = Product::updateOrCreate(
                ['name' => $data['name']],
                [
                    'category_id' => $data['category_id'],
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'price_lite' => $data['price_lite'],
                    'image' => $data['image'],
                    'is_active' => $data['is_active'],
                ]
            );

            // Sync ingredients
            $product->ingredients()->detach();
            if (isset($data['ingredients']['base'])) {
                foreach ($data['ingredients']['base'] as $id => $qty) {
                    $product->ingredients()->attach($id, [
                        'quantity' => $qty,
                        'variant' => isset($data['price_lite']) ? 'base' : null
                    ]);
                }
            }
            if (isset($data['ingredients']['lite'])) {
                foreach ($data['ingredients']['lite'] as $id => $qty) {
                    $product->ingredients()->attach($id, [
                        'quantity' => $qty,
                        'variant' => 'lite'
                    ]);
                }
            }
        }
    }
}
