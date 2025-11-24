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
        // Count items that currently have active inventory (not removed)
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
        // Use active inventory (inventories.removed_at IS NULL) to compute occupied/available
        $levels = ['A', 'B', 'C', 'D', 'E', 'F'];
        $locationUtilization = [
            'labels' => $levels,
            'available' => [],
            'occupied' => []
        ];

        foreach ($levels as $level) {
            // Total locations at this level
            $total = Location::where('level', $level)->count();

            // Occupied based on active inventory per location (group by level,height)
            $occupied = Location::select('locations.id')
                ->join('inventories', 'locations.id', '=', 'inventories.location_id')
                ->where('locations.level', $level)
                ->whereNull('inventories.removed_at')
                ->distinct()
                ->count('locations.id');

            $locationUtilization['occupied'][] = $occupied;
            $locationUtilization['available'][] = max(0, $total - $occupied);
        }

        return view('warehouse.dashboard', [
            'stats' => [
                'products' => Product::count(),
                'batches' => Batch::count(),
                // Compute available locations as those without any active inventory
                'available_locations' => Location::whereNotIn('id', function ($q) {
                    $q->select('location_id')->from('inventories')->whereNull('removed_at');
                })->count(),
                'reserved_locations' =>  LocationReservation::count(),
                'users' => User::count()
            ],
            'productDistribution' => $productDistribution,
            'locationUtilization' => $locationUtilization
        ]);
    }
}
