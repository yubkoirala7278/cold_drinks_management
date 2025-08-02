<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BatchesSeeder extends Seeder
{
    public function run()
    {
        $products = Product::all();
        $now = Carbon::now();

        foreach ($products as $product) {
            // Create 3 batches for each product with different dates
            for ($i = 1; $i <= 3; $i++) {
                $productionDate = $now->subDays($i * 30);
                $expiryDate = $productionDate->copy()->addYear();
                
                Batch::create([
                    'product_id' => $product->id,
                    'batch_number' => 'BATCH-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'production_date' => $productionDate,
                    'expiry_date' => $expiryDate,
                ]);
            }
        }
    }
}
