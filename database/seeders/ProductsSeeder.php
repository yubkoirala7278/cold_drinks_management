<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    public function run()
    {
        $products = [
            ['name' => 'Coca-Cola', 'sku' => 'CC-500', 'volume_ml' => 500],
            ['name' => 'Coca-Cola', 'sku' => 'CC-200', 'volume_ml' => 200],
            ['name' => 'Fanta', 'sku' => 'FA-500', 'volume_ml' => 500],
            ['name' => 'Fanta', 'sku' => 'FA-200', 'volume_ml' => 200],
            ['name' => 'Sprite', 'sku' => 'SP-500', 'volume_ml' => 500],
            ['name' => 'Sprite', 'sku' => 'SP-200', 'volume_ml' => 200],
            ['name' => 'Pepsi', 'sku' => 'PE-500', 'volume_ml' => 500],
            ['name' => 'Pepsi', 'sku' => 'PE-200', 'volume_ml' => 200],
            ['name' => 'Mirinda', 'sku' => 'MI-500', 'volume_ml' => 500],
            ['name' => 'Mirinda', 'sku' => 'MI-200', 'volume_ml' => 200],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
