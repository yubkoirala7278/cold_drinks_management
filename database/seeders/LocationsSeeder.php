<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationsSeeder extends Seeder
{
    public function run()
    {
        $levels = range('A', 'L');
        $heights = range(1, 6);
        $depths = range(1, 50);

        foreach ($levels as $level) {
            foreach ($heights as $height) {
                foreach ($depths as $depth) {
                    Location::create([
                        'level' => $level,
                        'height' => $height,
                        'depth' => $depth,
                        'product_id' => null,
                        'reserved' => false,
                    ]);
                }
            }
        }

        // Reserve some locations for Coca-Cola 500ml
        $coke500 = \App\Models\Product::where('sku', 'CC-500')->first();

        Location::where('level', 'A')
            ->where('height', 1)
            ->update([
                'product_id' => $coke500->id,
                'reserved' => true
            ]);

        // Reserve some locations for Fanta 500ml
        $fanta500 = \App\Models\Product::where('sku', 'FA-500')->first();

        Location::where('level', 'B')
            ->where('height', 1)
            ->update([
                'product_id' => $fanta500->id,
                'reserved' => true
            ]);
    }
}
