<?php

namespace Database\Seeders;

use App\Models\LocationReservation;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationReservationsSeeder extends Seeder
{
    public function run()
    {
        $coke500 = Product::where('sku', 'CC-500')->first();
        $fanta500 = Product::where('sku', 'FA-500')->first();
        $sprite200 = Product::where('sku', 'SP-200')->first();

        // Reserve entire level C for Coca-Cola 500ml
        LocationReservation::create([
            'product_id' => $coke500->id,
            'level' => 'C',
            'height' => null,
        ]);

        // Reserve height 2 on all levels for Fanta 500ml
        LocationReservation::create([
            'product_id' => $fanta500->id,
            'level' => null,
            'height' => 2,
        ]);

        // Reserve specific location for Sprite 200ml
        LocationReservation::create([
            'product_id' => $sprite200->id,
            'level' => 'D',
            'height' => 3,
        ]);
    }
}
