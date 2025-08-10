<?php

// app/Http/Controllers/WarehouseController.php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Batch;
use App\Models\Item;
use App\Models\Location;
use App\Models\LocationReservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function dashboard()
    {
        // Product Distribution Data
        $products = Product::select('name', 'volume_ml')
            ->orderBy('volume_ml')
            ->limit(8)
            ->get();

        $productDistribution = [
            'labels' => $products->pluck('name')->toArray(),
            'volumes' => $products->pluck('volume_ml')->toArray()
        ];

        // Location Utilization Data
        $levels = ['A', 'B', 'C', 'D', 'E', 'F'];
        $locationUtilization = [
            'labels' => $levels,
            'available' => [],
            'occupied' => []
        ];

        foreach ($levels as $level) {
            $locationUtilization['available'][] = Location::where('level', $level)
                ->whereNull('product_id')
                ->count();

            $locationUtilization['occupied'][] = Location::where('level', $level)
                ->whereNotNull('product_id')
                ->count();
        }

        // Batch Status Data
        $batchStatus = [
            'active' => Batch::where('expiry_date', '>', now())->count(),
            'expiring' => Batch::whereBetween('expiry_date', [now(), now()->addDays(30)])->count(),
            'expired' => Batch::where('expiry_date', '<', now())->count()
        ];

        return view('warehouse.dashboard', [
            'stats' => [
                'products' => Product::count(),
                'batches' => Batch::count(),
                'items' => Item::count(),
                'available_locations' => Location::whereNull('product_id')->count(),
                'reserved_locations' => LocationReservation::count(),
                'low_stock' => Product::has('batches', '<', 3)->count(),
                'expiring_soon' => $batchStatus['expiring'],
                'users' => User::count()
            ],
            'productDistribution' => $productDistribution,
            'locationUtilization' => $locationUtilization,
            'batchStatus' => $batchStatus
        ]);
    }
}
