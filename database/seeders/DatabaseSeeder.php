<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default pemilik (owner) account
        User::firstOrCreate(['email' => 'pemilik@cikscoffee.com'], [
            'name' => 'Pemilik Ciks Coffee',
            'password' => bcrypt('password'),
            'role' => 'pemilik',
            'is_active' => true,
        ]);

        // Create sample karyawan (employee) account
        User::firstOrCreate(['email' => 'karyawan@cikscoffee.com'], [
            'name' => 'Karyawan Demo',
            'password' => bcrypt('password'),
            'role' => 'karyawan',
            'is_active' => true,
        ]);

        $this->call(MenuCatalogSeeder::class);
    }
}
