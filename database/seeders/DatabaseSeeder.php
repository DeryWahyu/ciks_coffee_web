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
        User::factory()->create([
            'name' => 'Pemilik Ciks Coffee',
            'email' => 'pemilik@cikscoffee.com',
            'password' => bcrypt('password'),
            'role' => 'pemilik',
            'is_active' => true,
        ]);

        // Create sample karyawan (employee) account
        User::factory()->create([
            'name' => 'Karyawan Demo',
            'email' => 'karyawan@cikscoffee.com',
            'password' => bcrypt('password'),
            'role' => 'karyawan',
            'is_active' => true,
        ]);
    }
}
