<?php

// app/Http/Controllers/WarehouseController.php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Batch;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\LocationReservation;
use App\Models\User;
use Carbon\Carbon;

class WarehouseController extends Controller
{
    public function dashboard()
    {
        // Product Distribution Data (top 8 products by item count)
        $products = Product::withCount(['items as items_count' => function ($query) {
            $query->whereHas('inventory', function ($q) {
                $q->whereNull('removed_at');
            });
        }])
            ->orderByDesc('items_count')
            ->limit(8)
            ->get();

        $productDistribution = [
            'labels' => $products->map(function ($product) {
                return $product->name . ' (' . $product->sku . ')';
            })->toArray(),
            'item_counts' => $products->pluck('items_count')->toArray()
        ];

        // Location Utilization Data (all levels A-F)
        $levels = ['A', 'B', 'C', 'D', 'E', 'F'];
        $locationUtilization = [
            'labels' => $levels,
            'available' => [],
            'occupied' => []
        ];

        foreach ($levels as $level) {
            $locationUtilization['available'][] = Location::where('level', $level)
                ->whereNull('current_sku')
                ->count();

            $locationUtilization['occupied'][] = Location::where('level', $level)
                ->whereNotNull('current_sku')
                ->count();
        }

        return view('warehouse.dashboard', [
            'stats' => [
                'products' => Product::count(),
                'batches' => Batch::count(),
                'available_locations' => Location::whereNull('current_sku')->count(),
                'reserved_locations' =>  LocationReservation::count(),
                'users' => User::count()
            ],
            'productDistribution' => $productDistribution,
            'locationUtilization' => $locationUtilization
        ]);
    }
}
