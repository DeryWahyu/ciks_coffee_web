<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // ═══════════════════════════════════════════════
        // 1. BAHAN BAKU (Ingredients)
        // ═══════════════════════════════════════════════

        $ingredients = [
            // Bahan kopi
            ['nama_bahan' => 'Biji Kopi Arabika',    'satuan' => 'gram', 'stok' => 5000],
            ['nama_bahan' => 'Biji Kopi Robusta',     'satuan' => 'gram', 'stok' => 5000],
            ['nama_bahan' => 'Espresso Shot',         'satuan' => 'ml',   'stok' => 3000],

            // Susu & krim
            ['nama_bahan' => 'Susu Full Cream',       'satuan' => 'ml',   'stok' => 10000],
            ['nama_bahan' => 'Susu Oat',              'satuan' => 'ml',   'stok' => 5000],
            ['nama_bahan' => 'Whipped Cream',         'satuan' => 'ml',   'stok' => 3000],
            ['nama_bahan' => 'Susu Cokelat',          'satuan' => 'ml',   'stok' => 5000],

            // Sirup & saus
            ['nama_bahan' => 'Sirup Vanilla',         'satuan' => 'ml',   'stok' => 2000],
            ['nama_bahan' => 'Sirup Caramel',         'satuan' => 'ml',   'stok' => 2000],
            ['nama_bahan' => 'Sirup Hazelnut',        'satuan' => 'ml',   'stok' => 2000],
            ['nama_bahan' => 'Saus Cokelat',          'satuan' => 'ml',   'stok' => 3000],
            ['nama_bahan' => 'Sirup Matcha',          'satuan' => 'gram', 'stok' => 1500],

            // Teh
            ['nama_bahan' => 'Teh Earl Grey',         'satuan' => 'gram', 'stok' => 2000],
            ['nama_bahan' => 'Teh Hijau',             'satuan' => 'gram', 'stok' => 2000],
            ['nama_bahan' => 'Teh Chamomile',         'satuan' => 'gram', 'stok' => 1500],
            ['nama_bahan' => 'Teh Lychee',            'satuan' => 'gram', 'stok' => 1500],

            // Buah & jus
            ['nama_bahan' => 'Sari Lemon',            'satuan' => 'ml',   'stok' => 3000],
            ['nama_bahan' => 'Sari Jeruk',            'satuan' => 'ml',   'stok' => 3000],
            ['nama_bahan' => 'Sari Strawberry',       'satuan' => 'ml',   'stok' => 2000],
            ['nama_bahan' => 'Sari Mangga',           'satuan' => 'ml',   'stok' => 2000],

            // Pemanis & topping
            ['nama_bahan' => 'Gula Aren',             'satuan' => 'gram', 'stok' => 3000],
            ['nama_bahan' => 'Gula Pasir',            'satuan' => 'gram', 'stok' => 5000],
            ['nama_bahan' => 'Madu',                  'satuan' => 'ml',   'stok' => 2000],
            ['nama_bahan' => 'Es Batu',               'satuan' => 'pcs',  'stok' => 10000],

            // Bahan makanan
            ['nama_bahan' => 'Roti Tawar',            'satuan' => 'pcs',  'stok' => 200],
            ['nama_bahan' => 'Keju Cheddar',          'satuan' => 'gram', 'stok' => 2000],
            ['nama_bahan' => 'Daging Ayam',           'satuan' => 'gram', 'stok' => 3000],
            ['nama_bahan' => 'Nasi',                  'satuan' => 'gram', 'stok' => 5000],
            ['nama_bahan' => 'Telur',                 'satuan' => 'pcs',  'stok' => 500],
            ['nama_bahan' => 'Sosis',                 'satuan' => 'pcs',  'stok' => 300],
            ['nama_bahan' => 'Kentang',               'satuan' => 'gram', 'stok' => 3000],
            ['nama_bahan' => 'Tepung Terigu',         'satuan' => 'gram', 'stok' => 3000],
            ['nama_bahan' => 'Mentega',               'satuan' => 'gram', 'stok' => 2000],
            ['nama_bahan' => 'Selai Cokelat',         'satuan' => 'gram', 'stok' => 1500],
            ['nama_bahan' => 'Pisang',                'satuan' => 'pcs',  'stok' => 200],
            ['nama_bahan' => 'Soda Water',            'satuan' => 'ml',   'stok' => 5000],
        ];

        $ingredientMap = [];
        foreach ($ingredients as $data) {
            $ing = Ingredient::firstOrCreate(
                ['nama_bahan' => $data['nama_bahan']],
                $data
            );
            $ingredientMap[$data['nama_bahan']] = $ing->id;
        }

        // ═══════════════════════════════════════════════
        // 2. KATEGORI (pastikan ada)
        // ═══════════════════════════════════════════════

        $this->call(CategorySeeder::class);

        $categories = Category::pluck('id', 'slug');

        // ═══════════════════════════════════════════════
        // 3. PRODUK & RESEP (Products + Ingredient pivot)
        // ═══════════════════════════════════════════════

        // ───── COFFEE (punya variant base & lite) ─────
        $coffeeProducts = [
            [
                'name' => 'Espresso',
                'description' => 'Espresso murni yang kaya dan bold, diseduh dari biji pilihan.',
                'price' => 15000,
                'price_lite' => 12000,
                'ingredients' => [
                    'base' => [
                        'Biji Kopi Arabika' => 18,
                        'Espresso Shot' => 30,
                    ],
                    'lite' => [
                        'Biji Kopi Arabika' => 14,
                        'Espresso Shot' => 20,
                    ],
                ],
            ],
            [
                'name' => 'Americano',
                'description' => 'Espresso dengan air panas, ringan namun tetap berkarakter.',
                'price' => 18000,
                'price_lite' => 14000,
                'ingredients' => [
                    'base' => [
                        'Biji Kopi Arabika' => 18,
                        'Espresso Shot' => 30,
                    ],
                    'lite' => [
                        'Biji Kopi Arabika' => 14,
                        'Espresso Shot' => 20,
                    ],
                ],
            ],
            [
                'name' => 'Cappuccino',
                'description' => 'Perpaduan espresso, susu steamed, dan foam lembut.',
                'price' => 22000,
                'price_lite' => 18000,
                'ingredients' => [
                    'base' => [
                        'Biji Kopi Arabika' => 18,
                        'Espresso Shot' => 30,
                        'Susu Full Cream' => 150,
                    ],
                    'lite' => [
                        'Biji Kopi Arabika' => 14,
                        'Espresso Shot' => 20,
                        'Susu Full Cream' => 100,
                    ],
                ],
            ],
            [
                'name' => 'Cafe Latte',
                'description' => 'Espresso dengan susu steamed yang creamy dan lembut.',
                'price' => 24000,
                'price_lite' => 20000,
                'ingredients' => [
                    'base' => [
                        'Biji Kopi Arabika' => 18,
                        'Espresso Shot' => 30,
                        'Susu Full Cream' => 200,
                    ],
                    'lite' => [
                        'Biji Kopi Arabika' => 14,
                        'Espresso Shot' => 20,
                        'Susu Full Cream' => 130,
                    ],
                ],
            ],
            [
                'name' => 'Caramel Macchiato',
                'description' => 'Latte dengan siraman sirup caramel dan vanilla yang manis.',
                'price' => 28000,
                'price_lite' => 23000,
                'ingredients' => [
                    'base' => [
                        'Biji Kopi Arabika' => 18,
                        'Espresso Shot' => 30,
                        'Susu Full Cream' => 200,
                        'Sirup Caramel' => 20,
                        'Sirup Vanilla' => 10,
                    ],
                    'lite' => [
                        'Biji Kopi Arabika' => 14,
                        'Espresso Shot' => 20,
                        'Susu Full Cream' => 130,
                        'Sirup Caramel' => 15,
                        'Sirup Vanilla' => 5,
                    ],
                ],
            ],
            [
                'name' => 'Mocha Latte',
                'description' => 'Perpaduan sempurna espresso, susu, dan cokelat premium.',
                'price' => 26000,
                'price_lite' => 22000,
                'ingredients' => [
                    'base' => [
                        'Biji Kopi Arabika' => 18,
                        'Espresso Shot' => 30,
                        'Susu Full Cream' => 180,
                        'Saus Cokelat' => 25,
                        'Whipped Cream' => 30,
                    ],
                    'lite' => [
                        'Biji Kopi Arabika' => 14,
                        'Espresso Shot' => 20,
                        'Susu Full Cream' => 120,
                        'Saus Cokelat' => 15,
                    ],
                ],
            ],
            [
                'name' => 'Hazelnut Latte',
                'description' => 'Latte dengan aroma hazelnut yang menggoda.',
                'price' => 26000,
                'price_lite' => 22000,
                'ingredients' => [
                    'base' => [
                        'Biji Kopi Arabika' => 18,
                        'Espresso Shot' => 30,
                        'Susu Full Cream' => 200,
                        'Sirup Hazelnut' => 20,
                    ],
                    'lite' => [
                        'Biji Kopi Arabika' => 14,
                        'Espresso Shot' => 20,
                        'Susu Full Cream' => 130,
                        'Sirup Hazelnut' => 15,
                    ],
                ],
            ],
            [
                'name' => 'Kopi Susu Gula Aren',
                'description' => 'Es kopi susu khas Indonesia dengan gula aren asli.',
                'price' => 22000,
                'price_lite' => 18000,
                'ingredients' => [
                    'base' => [
                        'Biji Kopi Robusta' => 20,
                        'Espresso Shot' => 40,
                        'Susu Full Cream' => 150,
                        'Gula Aren' => 25,
                        'Es Batu' => 5,
                    ],
                    'lite' => [
                        'Biji Kopi Robusta' => 15,
                        'Espresso Shot' => 25,
                        'Susu Full Cream' => 100,
                        'Gula Aren' => 15,
                        'Es Batu' => 4,
                    ],
                ],
            ],
        ];

        foreach ($coffeeProducts as $data) {
            $product = Product::firstOrCreate(
                ['name' => $data['name'], 'category_id' => $categories['coffee']],
                [
                    'category_id'  => $categories['coffee'],
                    'description'  => $data['description'],
                    'price'        => $data['price'],
                    'price_lite'   => $data['price_lite'],
                    'is_active'    => true,
                ]
            );

            // Attach base variant ingredients
            foreach ($data['ingredients']['base'] as $ingredientName => $qty) {
                $product->ingredients()->syncWithoutDetaching([
                    $ingredientMap[$ingredientName] => ['quantity' => $qty, 'variant' => 'base'],
                ]);
            }
            // Attach lite variant ingredients
            foreach ($data['ingredients']['lite'] as $ingredientName => $qty) {
                $product->ingredients()->syncWithoutDetaching([
                    $ingredientMap[$ingredientName] => ['quantity' => $qty, 'variant' => 'lite'],
                ]);
            }
        }

        // ───── MILK (non-coffee milk drinks, no variant) ─────
        $milkProducts = [
            [
                'name' => 'Choco Milk',
                'description' => 'Susu cokelat premium yang rich dan creamy.',
                'price' => 20000,
                'ingredients' => [
                    'Susu Cokelat' => 250,
                    'Saus Cokelat' => 15,
                    'Es Batu' => 5,
                ],
            ],
            [
                'name' => 'Matcha Latte',
                'description' => 'Matcha Jepang berkualitas dengan susu lembut.',
                'price' => 24000,
                'ingredients' => [
                    'Sirup Matcha' => 15,
                    'Susu Full Cream' => 250,
                    'Es Batu' => 5,
                ],
            ],
            [
                'name' => 'Vanilla Milk',
                'description' => 'Susu segar dengan sirup vanilla yang harum.',
                'price' => 18000,
                'ingredients' => [
                    'Susu Full Cream' => 250,
                    'Sirup Vanilla' => 20,
                    'Es Batu' => 5,
                ],
            ],
            [
                'name' => 'Caramel Milk',
                'description' => 'Susu creamy dengan saus caramel yang manis.',
                'price' => 20000,
                'ingredients' => [
                    'Susu Full Cream' => 250,
                    'Sirup Caramel' => 20,
                    'Es Batu' => 5,
                ],
            ],
            [
                'name' => 'Oat Milk Honey',
                'description' => 'Susu oat sehat dengan madu alami.',
                'price' => 22000,
                'ingredients' => [
                    'Susu Oat' => 250,
                    'Madu' => 20,
                    'Es Batu' => 5,
                ],
            ],
        ];

        $this->seedSimpleProducts($milkProducts, $categories['milk'], $ingredientMap);

        // ───── TEA ─────
        $teaProducts = [
            [
                'name' => 'Earl Grey Tea',
                'description' => 'Teh Earl Grey klasik dengan aroma bergamot.',
                'price' => 15000,
                'ingredients' => [
                    'Teh Earl Grey' => 5,
                    'Gula Pasir' => 15,
                ],
            ],
            [
                'name' => 'Green Tea Latte',
                'description' => 'Teh hijau premium dicampur susu lembut.',
                'price' => 22000,
                'ingredients' => [
                    'Teh Hijau' => 5,
                    'Susu Full Cream' => 200,
                    'Gula Pasir' => 10,
                    'Es Batu' => 5,
                ],
            ],
            [
                'name' => 'Chamomile Tea',
                'description' => 'Teh chamomile yang menenangkan dan aromatik.',
                'price' => 18000,
                'ingredients' => [
                    'Teh Chamomile' => 5,
                    'Madu' => 15,
                ],
            ],
            [
                'name' => 'Lychee Tea',
                'description' => 'Teh segar dengan rasa leci yang manis alami.',
                'price' => 20000,
                'ingredients' => [
                    'Teh Lychee' => 5,
                    'Gula Pasir' => 10,
                    'Es Batu' => 5,
                ],
            ],
            [
                'name' => 'Lemon Tea',
                'description' => 'Teh segar dengan perasan lemon asli.',
                'price' => 16000,
                'ingredients' => [
                    'Teh Earl Grey' => 4,
                    'Sari Lemon' => 30,
                    'Gula Pasir' => 15,
                    'Es Batu' => 5,
                ],
            ],
        ];

        $this->seedSimpleProducts($teaProducts, $categories['tea'], $ingredientMap);

        // ───── SQUASH ─────
        $squashProducts = [
            [
                'name' => 'Lemon Squash',
                'description' => 'Minuman lemon segar dengan soda yang menyegarkan.',
                'price' => 18000,
                'ingredients' => [
                    'Sari Lemon' => 40,
                    'Gula Pasir' => 20,
                    'Soda Water' => 150,
                    'Es Batu' => 6,
                ],
            ],
            [
                'name' => 'Orange Squash',
                'description' => 'Jeruk segar berpadu soda, cocok di cuaca panas.',
                'price' => 18000,
                'ingredients' => [
                    'Sari Jeruk' => 50,
                    'Gula Pasir' => 15,
                    'Soda Water' => 150,
                    'Es Batu' => 6,
                ],
            ],
            [
                'name' => 'Strawberry Squash',
                'description' => 'Strawberry manis asam dengan soda sparkling.',
                'price' => 20000,
                'ingredients' => [
                    'Sari Strawberry' => 50,
                    'Gula Pasir' => 15,
                    'Soda Water' => 150,
                    'Es Batu' => 6,
                ],
            ],
            [
                'name' => 'Mango Squash',
                'description' => 'Mangga tropis segar dengan gelembung soda.',
                'price' => 20000,
                'ingredients' => [
                    'Sari Mangga' => 50,
                    'Gula Pasir' => 15,
                    'Soda Water' => 150,
                    'Es Batu' => 6,
                ],
            ],
        ];

        $this->seedSimpleProducts($squashProducts, $categories['squash'], $ingredientMap);

        // ───── SNACK ─────
        $snackProducts = [
            [
                'name' => 'Roti Bakar Cokelat',
                'description' => 'Roti panggang dengan selai cokelat premium.',
                'price' => 15000,
                'ingredients' => [
                    'Roti Tawar' => 2,
                    'Mentega' => 10,
                    'Selai Cokelat' => 30,
                ],
            ],
            [
                'name' => 'Roti Bakar Keju',
                'description' => 'Roti panggang dengan keju cheddar meleleh.',
                'price' => 16000,
                'ingredients' => [
                    'Roti Tawar' => 2,
                    'Mentega' => 10,
                    'Keju Cheddar' => 40,
                ],
            ],
            [
                'name' => 'French Fries',
                'description' => 'Kentang goreng renyah dengan bumbu spesial.',
                'price' => 18000,
                'ingredients' => [
                    'Kentang' => 200,
                ],
            ],
            [
                'name' => 'Pisang Goreng',
                'description' => 'Pisang goreng crispy dengan topping keju & cokelat.',
                'price' => 15000,
                'ingredients' => [
                    'Pisang' => 3,
                    'Tepung Terigu' => 50,
                    'Keju Cheddar' => 15,
                    'Saus Cokelat' => 10,
                ],
            ],
            [
                'name' => 'Sosis Bakar',
                'description' => 'Sosis premium bakar dengan saus spesial.',
                'price' => 14000,
                'ingredients' => [
                    'Sosis' => 2,
                ],
            ],
        ];

        $this->seedSimpleProducts($snackProducts, $categories['snack'], $ingredientMap);

        // ───── MEAL ─────
        $mealProducts = [
            [
                'name' => 'Nasi Goreng Spesial',
                'description' => 'Nasi goreng dengan ayam, telur, dan pelengkap.',
                'price' => 25000,
                'ingredients' => [
                    'Nasi' => 250,
                    'Daging Ayam' => 80,
                    'Telur' => 1,
                    'Mentega' => 10,
                ],
            ],
            [
                'name' => 'Chicken Sandwich',
                'description' => 'Sandwich ayam panggang dengan keju dan sayuran.',
                'price' => 28000,
                'ingredients' => [
                    'Roti Tawar' => 2,
                    'Daging Ayam' => 100,
                    'Keju Cheddar' => 30,
                    'Mentega' => 10,
                ],
            ],
            [
                'name' => 'Nasi Ayam Geprek',
                'description' => 'Nasi dengan ayam geprek crispy dan sambal.',
                'price' => 27000,
                'ingredients' => [
                    'Nasi' => 250,
                    'Daging Ayam' => 120,
                    'Tepung Terigu' => 40,
                ],
            ],
            [
                'name' => 'Omelette Keju',
                'description' => 'Omelette fluffy dengan keju cheddar meleleh.',
                'price' => 20000,
                'ingredients' => [
                    'Telur' => 3,
                    'Keju Cheddar' => 40,
                    'Mentega' => 10,
                ],
            ],
        ];

        $this->seedSimpleProducts($mealProducts, $categories['meal'], $ingredientMap);
    }

    /**
     * Helper: seed products without variant (non-coffee).
     */
    private function seedSimpleProducts(array $products, int $categoryId, array $ingredientMap): void
    {
        foreach ($products as $data) {
            $product = Product::firstOrCreate(
                ['name' => $data['name'], 'category_id' => $categoryId],
                [
                    'category_id'  => $categoryId,
                    'description'  => $data['description'],
                    'price'        => $data['price'],
                    'price_lite'   => null,
                    'is_active'    => true,
                ]
            );

            foreach ($data['ingredients'] as $ingredientName => $qty) {
                $product->ingredients()->syncWithoutDetaching([
                    $ingredientMap[$ingredientName] => ['quantity' => $qty, 'variant' => null],
                ]);
            }
        }
    }
}
