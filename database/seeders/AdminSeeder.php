<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);

        // Create Inbound Staff
        User::create([
            'name' => 'Inbound Staff',
            'email' => 'inbound@inbound.com',
            'password' => bcrypt('password'),
            'role' => 'inbound_staff'
        ]);

        // Create Outbound Staff
        User::create([
            'name' => 'Outbound Staff',
            'email' => 'outbound@outbound.com',
            'password' => bcrypt('password'),
            'role' => 'outbound_staff'
        ]);
    }
}
