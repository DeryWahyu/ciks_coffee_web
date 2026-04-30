<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Coffee', 'Milk', 'Snack', 'Squash', 'Tea', 'Meal'];

        foreach ($categories as $name) {
            Category::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name, 'is_active' => true]
            );
        }
    }
}
