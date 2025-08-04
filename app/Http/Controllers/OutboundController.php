<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// app/Http/Controllers/OutboundController.php
class OutboundController extends Controller
{
    // 1: Load Outbound Page
    public function outbound()
    {
        $products = Product::all();
        $todayOutboundCount = Inventory::whereDate('removed_at', today())->count();

        return view('warehouse.outbound', [
            'products' => $products,
            'todayOutboundCount' => $todayOutboundCount
        ]);
    }

    // 2: Get Oldest Place for Outbound
    public function getNextItem(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        // Find the next available item
        $inventory = Inventory::whereHas('item.batch', function ($query) use ($request) {
            $query->where('product_id', $request->product_id);
        })
            ->whereNull('removed_at')
            ->orderBy('placed_at')
            ->first();

        if (!$inventory) {
            return response()->json([
                'error' => 'No items available for this product',
                'no_items' => true
            ], 200);
        }

        // Check if this is the last item in its location
        $location = $inventory->location;
        $itemsInLocation = Inventory::where('location_id', $location->id)
            ->whereNull('removed_at')
            ->count();

        return response()->json([
            'location' => $location->level . $location->height . '-S' . str_pad($location->depth, 2, '0', STR_PAD_LEFT),
            'barcode' => $inventory->item->barcode,
            'location_id' => $location->id,
            'is_last_in_location' => ($itemsInLocation === 1)
        ]);
    }

    // 3: Remove Product
    public function removeItem(Request $request)
    {
        $request->validate([
            'barcode' => 'required|exists:items,barcode',
            'location_id' => 'required|exists:locations,id'
        ]);

        DB::transaction(function () use ($request) {
            // Mark item as removed
            $inventory = Inventory::with('location')
                ->whereHas('item', function ($query) use ($request) {
                    $query->where('barcode', $request->barcode);
                })
                ->where('location_id', $request->location_id)
                ->whereNull('removed_at')
                ->firstOrFail();

            $location = $inventory->location;
            $inventory->update(['removed_at' => now()]);

            // Shift items forward in this location column
            $this->shiftItemsForward($location);
        });

        return response()->json([
            'success' => true,
            'stats' => [
                'today_outbound' => Inventory::whereDate('removed_at', today())->count()
            ]
        ]);
    }

    // 4: After Removing Shift other Products by 1 place ahead
    private function shiftItemsForward(Location $removedLocation)
    {
        // Get all locations in the same column with LOWER depth (shallower), ordered descending
        $locationsToShift = Location::where('level', $removedLocation->level)
            ->where('height', $removedLocation->height)
            ->where('depth', '<', $removedLocation->depth)
            ->orderBy('depth', 'desc') // Process from deepest to shallowest
            ->get();

        foreach ($locationsToShift as $location) {
            // Find active inventory item at this location
            $inventory = Inventory::with('location')
                ->where('location_id', $location->id)
                ->whereNull('removed_at')
                ->first();

            if ($inventory) {
                $newDepth = $location->depth + 1; // Move deeper

                $newLocation = Location::where('level', $removedLocation->level)
                    ->where('height', $removedLocation->height)
                    ->where('depth', $newDepth)
                    ->first();

                if ($newLocation) {
                    // Update the inventory item's location
                    $inventory->update([
                        'location_id' => $newLocation->id
                    ]);
                }
            }
        }
    }
}
