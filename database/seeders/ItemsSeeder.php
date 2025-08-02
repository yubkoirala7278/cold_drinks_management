<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemsSeeder extends Seeder
{
    public function run()
    {
        $batches = Batch::with('product')->get();
        $barcodeCounter = 1;

        foreach ($batches as $batch) {
            // Create 20 items for each batch
            for ($i = 0; $i < 20; $i++) {
                $barcode = str_pad($barcodeCounter++, 4, '0', STR_PAD_LEFT);

                $item = Item::create([
                    'batch_id' => $batch->id,
                    'barcode' => $barcode,
                ]);

                // For every 5th item, assign to inventory
                if ($i % 5 === 0) {
                    $this->assignItemToLocation($item, $batch->product);
                }
            }
        }
    }

    protected function assignItemToLocation($item, $product)
    {
        // Find a suitable location for this product
        $location = Location::where('product_id', $product->id)
            ->whereDoesntHave('inventory')
            ->orderBy('level')
            ->orderBy('height')
            ->orderByDesc('depth')
            ->first();

        if (!$location) {
            // If no reserved location, find any available location
            $location = Location::where('product_id', null)
                ->where('reserved', false)
                ->whereDoesntHave('inventory')
                ->orderBy('level')
                ->orderBy('height')
                ->orderByDesc('depth')
                ->first();
        }

        if ($location) {
            Inventory::create([
                'item_id' => $item->id,
                'location_id' => $location->id,
                'placed_at' => now()->subDays(rand(1, 60)),
            ]);
        }
    }
}
