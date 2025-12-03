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

        // Enforce use of the configured system inbound barcode
        $inboundCode = config('warehouse.inbound_barcode');
        if ($validated['barcode'] !== $inboundCode) {
            return response()->json([
                'error' => 'Invalid inbound barcode. Please scan the system inbound barcode.',
                'invalid_barcode' => true
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
            // Allow duplicate barcodes because a system-wide inbound barcode may be used
            'barcode' => 'required',
            'location_id' => 'required|exists:locations,id'
        ]);

        // Validate inbound barcode matches system code before storing
        $inboundCode = config('warehouse.inbound_barcode');
        if ($validated['barcode'] !== $inboundCode) {
            return response()->json([
                'error' => 'Invalid inbound barcode. Please scan the system inbound barcode.',
                'invalid_barcode' => true
            ], 200);
        }

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
        // Check if this product has any reserved locations
        $hasReservations = LocationReservation::where('product_id', $product->id)->exists();

        // If product has reserved columns, prioritize them first
        if ($hasReservations) {
            // 1. First check reserved locations (top priority for reserved products)
            if ($location = $this->findInReservedLocations($product)) {
                return $location;
            }

            // 2. Then try existing SKU columns
            if ($location = $this->findInExistingSkuColumns($product)) {
                return $location;
            }
        } else {
            // No reservations: prioritize existing SKU columns, then empty columns
            // 1. Try to continue in existing columns with same SKU
            if ($location = $this->findInExistingSkuColumns($product)) {
                return $location;
            }
        }

        // 3. Find any empty level+height (not reserved for another product)
        return $this->findEmptyLocationForSku($product);
    }

    private function findInExistingSkuColumns(Product $product): ?Location
    {
        // Find all columns (level+height) that currently have this product's SKU
        $columns = DB::table('locations')
            ->select('level', 'height')
            ->where('current_sku', $product->sku)
            ->groupBy('level', 'height')
            ->orderBy('level')
            ->orderBy('height')
            ->get();

        foreach ($columns as $column) {
            // Find deepest available slot in this column (no active inventory at that depth)
            $location = Location::where('level', $column->level)
                ->where('height', $column->height)
                ->whereDoesntHave('inventory', fn($q) => $q->whereNull('removed_at'))
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
        // Get all LocationReservation records for this product (columns reserved for this SKU)
        $reservations = LocationReservation::where('product_id', $product->id)
            ->orderBy('level')
            ->orderBy('height')
            ->get();

        foreach ($reservations as $reservation) {
            // Find deepest empty spot in this reserved column
            $location = Location::where('level', $reservation->level)
                ->where('height', $reservation->height)
                ->whereDoesntHave('inventory', fn($q) => $q->whereNull('removed_at'))
                ->orderByDesc('depth')
                ->first();

            if ($location) {
                // Mark location with product info and SKU
                $location->update([
                    'product_id' => $product->id,
                    'current_sku' => $product->sku
                ]);
                return $location;
            }
        }

        return null;
    }

    private function findEmptyLocationForSku(Product $product): ?Location
    {
        // Get all columns reserved for OTHER products (cannot use these)
        $reservedForOthers = LocationReservation::where('product_id', '!=', $product->id)
            ->get()
            ->mapWithKeys(fn($r) => [$r->level . $r->height => true])
            ->toArray();

        // Get columns with active inventory (currently occupied)
        $activeColumns = DB::table('locations')
            ->select('locations.level', 'locations.height')
            ->join('inventories', 'locations.id', '=', 'inventories.location_id')
            ->whereNull('inventories.removed_at')
            ->groupBy('locations.level', 'locations.height')
            ->get()
            ->mapWithKeys(fn($c) => [$c->level . $c->height => true])
            ->toArray();

        // Search all columns left-to-right (A..L, heights 1..6) for first available
        foreach (range('A', 'L') as $level) {
            foreach (range(1, 6) as $height) {
                $columnKey = $level . $height;

                // Skip if reserved for another product
                if (isset($reservedForOthers[$columnKey])) {
                    continue;
                }

                // Skip if has active inventory
                if (isset($activeColumns[$columnKey])) {
                    continue;
                }

                // Find deepest available spot in this empty column
                $location = Location::where('level', $level)
                    ->where('height', $height)
                    ->whereDoesntHave('inventory', fn($q) => $q->whereNull('removed_at'))
                    ->orderByDesc('depth')
                    ->first();

                if ($location) {
                    $location->update(['current_sku' => $product->sku]);
                    return $location;
                }
            }
        }

        return null;
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
