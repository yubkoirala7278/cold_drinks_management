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
        return view('warehouse.inbound', compact('products'));
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
            'barcode' => 'required|unique:items,barcode'
        ]);

        $product = Product::findOrFail($request->product_id);

        // First check if barcode already exists
        if (Item::where('barcode', $request->barcode)->exists()) {
            return response()->json(['error' => 'Barcode already exists'], 400);
        }

        // Find best location for the product
        $location = $this->findBestLocation($product);

        if (!$location) {
            return response()->json(['error' => 'No available locations found'], 404);
        }

        return response()->json([
            'location' => $location->level . $location->height . '-S' . str_pad($location->depth, 2, '0', STR_PAD_LEFT),
            'location_id' => $location->id
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

        return response()->json(['success' => true]);
    }

    public function outbound()
    {
        $products = Product::all();
        return view('warehouse.outbound', compact('products'));
    }

    public function getNextItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        // Find the oldest item for this product
        $inventory = Inventory::whereHas('item.batch', function ($query) use ($request) {
            $query->where('product_id', $request->product_id);
        })
            ->whereNull('removed_at')
            ->orderBy('placed_at')
            ->first();

        if (!$inventory) {
            return response()->json(['error' => 'No items available for this product'], 404);
        }

        return response()->json([
            'location' => $inventory->location->level . $inventory->location->height . '-S' . str_pad($inventory->location->depth, 2, '0', STR_PAD_LEFT),
            'barcode' => $inventory->item->barcode,
            'location_id' => $inventory->location_id
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

        return response()->json(['success' => true]);
    }

    private function getNextItemAtLocation($locationId)
    {
        $location = Location::findOrFail($locationId);

        return Inventory::whereHas('location', function ($query) use ($location) {
            $query->where('level', $location->level)
                ->where('height', $location->height)
                ->where('depth', $location->depth);
        })
            ->whereNull('removed_at')
            ->orderBy('placed_at')
            ->first();
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
