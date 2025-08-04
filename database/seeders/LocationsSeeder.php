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
    }
}
