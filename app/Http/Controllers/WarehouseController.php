<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// app/Http/Controllers/WarehouseController.php
class WarehouseController extends Controller
{
    public function dashboard()
    {
        return view('warehouse.dashboard');
    }

    public function inbound()
    {
        $products = Product::with('batches')->get();

        // Add today's inbound count
        $todayInboundCount = Inventory::whereDate('placed_at', today())->count();

        return view('warehouse.inbound', [
            'products' => $products,
            'todayInboundCount' => $todayInboundCount
        ]);
    }

    public function getBatches($productId)
    {
        $batches = Batch::where('product_id', $productId)->get();
        return response()->json($batches);
    }

    public function findLocation(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'batch_id' => 'required|exists:batches,id',
            'barcode' => 'required'
        ]);

        // First check if barcode already exists
        if (Item::where('barcode', $request->barcode)->exists()) {
            return response()->json([
                'error' => 'Barcode already exists in the system',
                'barcode_exists' => true
            ], 200);
        }

        $product = Product::findOrFail($request->product_id);

        // Find best location for the product
        $location = $this->findBestLocation($product);

        if (!$location) {
            return response()->json([
                'error' => 'No available locations found for this product',
                'no_location' => true
            ], 200);
        }

        return response()->json([
            'location' => $location->level . $location->height . '-S' . str_pad($location->depth, 2, '0', STR_PAD_LEFT),
            'location_id' => $location->id,
            'barcode_valid' => true
        ]);
    }

    public function storeItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'batch_id' => 'required|exists:batches,id',
            'barcode' => 'required|unique:items,barcode',
            'location_id' => 'required|exists:locations,id'
        ]);

        DB::transaction(function () use ($request) {
            // Create the item
            $item = Item::create([
                'batch_id' => $request->batch_id,
                'barcode' => $request->barcode
            ]);

            // Add to inventory
            Inventory::create([
                'item_id' => $item->id,
                'location_id' => $request->location_id,
                'placed_at' => now()
            ]);
        });

        return response()->json([
            'success' => true,
            'stats' => [
                'today_inbound' => Inventory::whereDate('placed_at', today())->count(),
                'session_increment' => 1 // Frontend will add this
            ]
        ]);
    }

    public function outbound()
    {
        $products = Product::all();
        $todayOutboundCount = Inventory::whereDate('removed_at', today())->count();

        return view('warehouse.outbound', [
            'products' => $products,
            'todayOutboundCount' => $todayOutboundCount
        ]);
    }

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


    private function findBestLocation(Product $product)
    {
        // 1. First try to find locations already assigned to this product with space
        $assignedLocation = Location::where('product_id', $product->id)
            ->where('reserved', true)
            ->whereDoesntHave('inventory')
            ->orderBy('level')
            ->orderBy('height')
            ->orderByDesc('depth') // Changed to DESC to get deepest available first
            ->first();

        if ($assignedLocation) {
            return $assignedLocation;
        }

        // 2. Check product's reserved areas
        $reservedLocations = $product->reservedLocations;

        if ($reservedLocations->isNotEmpty()) {
            foreach ($reservedLocations as $reservation) {
                $query = Location::where('product_id', null)
                    ->whereDoesntHave('inventory');

                if ($reservation->level) {
                    $query->where('level', $reservation->level);
                }

                if ($reservation->height) {
                    $query->where('height', $reservation->height);
                }

                $location = $query->orderByDesc('depth') // Changed to DESC
                    ->first();

                if ($location) {
                    $location->update([
                        'product_id' => $product->id,
                        'reserved' => true
                    ]);
                    return $location;
                }
            }
        }

        // 3. Find any empty location not reserved for another product
        return Location::where('product_id', null)
            ->where('reserved', false)
            ->whereDoesntHave('inventory')
            ->orderByDesc('depth') // Changed to DESC to fill from deepest first
            ->first();
    }

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
