<?php

namespace App\Http\Controllers;

use App\Models\{Batch, Inventory, Item, Location, Product, LocationReservation};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InboundController extends Controller
{
    // Display inbound page
    public function inbound()
    {
        $products = Product::with('batches')->get();
        $todayInboundCount = Inventory::whereDate('placed_at', today())->count();

        return view('warehouse.inbound', [
            'products' => $products,
            'todayInboundCount' => $todayInboundCount
        ]);
    }

    // Get batches for product
    public function getBatches($productId)
    {
        $batches = Batch::where('product_id', $productId)->get();
        return response()->json($batches);
    }

    // Find location for inbound item
    public function findLocation(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'batch_id' => 'required|exists:batches,id',
            'barcode' => 'required'
        ]);

        if (Item::where('barcode', $validated['barcode'])->exists()) {
            return response()->json([
                'error' => 'Barcode already exists',
                'barcode_exists' => true
            ], 200);
        }

        $product = Product::findOrFail($validated['product_id']);
        $location = $this->findBestLocation($product);

        if (!$location) {
            return response()->json([
                'error' => 'No available locations',
                'no_location' => true
            ], 200);
        }

        return response()->json([
            'location' => $this->formatLocation($location),
            'location_id' => $location->id,
            'barcode_valid' => true
        ]);
    }

    // Store inbound item
    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'batch_id' => 'required|exists:batches,id',
            'barcode' => 'required|unique:items,barcode',
            'location_id' => 'required|exists:locations,id'
        ]);

        DB::transaction(function () use ($validated) {
            $item = Item::create([
                'batch_id' => $validated['batch_id'],
                'barcode' => $validated['barcode']
            ]);

            Inventory::create([
                'item_id' => $item->id,
                'location_id' => $validated['location_id'],
                'placed_at' => now()
            ]);

            // Update location's current SKU if empty
            $location = Location::find($validated['location_id']);
            if (!$location->current_sku) {
                $location->update(['current_sku' => $item->batch->product->sku]);
            }
        });

        return response()->json([
            'success' => true,
            'stats' => [
                'today_inbound' => Inventory::whereDate('placed_at', today())->count(),
                'session_increment' => 1
            ]
        ]);
    }

    // ========== PRIVATE METHODS ========== //

    private function findBestLocation(Product $product): ?Location
    {
        // 1. First try to continue in existing columns with same SKU
        if ($location = $this->findInExistingSkuColumns($product)) {
            return $location;
        }

        // 2. Then check reserved locations
        if ($location = $this->findInReservedLocations($product)) {
            return $location;
        }

        // 3. Finally find any empty location
        return $this->findEmptyLocationForSku($product);
    }

    private function findInExistingSkuColumns(Product $product): ?Location
    {
        // Find all columns containing this SKU
        $columns = DB::table('locations')
            ->select('level', 'height')
            ->where('current_sku', $product->sku)
            ->groupBy('level', 'height')
            ->get();

        foreach ($columns as $column) {
            // Get deepest available spot in this column
            $location = Location::where('level', $column->level)
                ->where('height', $column->height)
                ->whereDoesntHave('inventory')
                ->orderByDesc('depth')
                ->first();

            if ($location) {
                return $location;
            }
        }

        return null;
    }

    private function findInReservedLocations(Product $product): ?Location
    {
        // Check specifically reserved locations first
        $reserved = Location::where('product_id', $product->id)
            ->where('reserved', true)
            ->whereDoesntHave('inventory')
            ->orderByDesc('depth')
            ->first();

        if ($reserved) {
            $reserved->update(['current_sku' => $product->sku]);
            return $reserved;
        }

        // Then check reserved zones
        return $this->findInReservedZones($product);
    }

    private function findInReservedZones(Product $product): ?Location
    {
        $reservations = LocationReservation::where('product_id', $product->id)->get();

        foreach ($reservations as $reservation) {
            $location = Location::where('level', $reservation->level)
                ->where('height', $reservation->height)
                ->whereDoesntHave('inventory')
                ->whereNull('current_sku')
                ->orderByDesc('depth')
                ->first();

            if ($location) {
                $location->update([
                    'product_id' => $product->id,
                    'reserved' => true,
                    'current_sku' => $product->sku
                ]);
                return $location;
            }
        }

        return null;
    }

    private function findEmptyLocationForSku(Product $product): ?Location
    {
        // Find any completely empty column
        $location = Location::whereDoesntHave('inventory')
            ->whereNull('current_sku')
            ->orderByDesc('depth')
            ->first();

        if ($location) {
            $location->update(['current_sku' => $product->sku]);
        }

        return $location;
    }

    private function formatLocation(Location $location): string
    {
        return sprintf(
            '%s%s-S%02d',
            $location->level,
            $location->height,
            $location->depth
        );
    }
}
