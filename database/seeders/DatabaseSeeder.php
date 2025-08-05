<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         $this->call([
            AdminSeeder::class,
            ProductsSeeder::class,
            LocationsSeeder::class,
            BatchesSeeder::class,
            // ItemsSeeder::class,
            // LocationReservationsSeeder::class,
        ]);
    }
}
