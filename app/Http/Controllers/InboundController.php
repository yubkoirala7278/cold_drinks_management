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

    // Display bulk inbound page (for admin to sync existing warehouse stock)
    public function bulkInbound()
    {
        $products = Product::with('batches')->get();
        return view('warehouse.bulk-inbound', ['products' => $products]);
    }

    // Get available locations for bulk inbound
    public function getAvailableLocations(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);
        $productId = $request->product_id;
        $product = Product::findOrFail($productId);

        // Get available locations following same logic as regular inbound
        $locations = [];

        // 1. Existing SKU columns
        $existingColumns = DB::table('locations')
            ->select('level', 'height')
            ->where('current_sku', $product->sku)
            ->groupBy('level', 'height')
            ->orderBy('level')
            ->orderBy('height')
            ->get();

        foreach ($existingColumns as $column) {
            $availableCount = Location::where('level', $column->level)
                ->where('height', $column->height)
                ->whereDoesntHave('inventory', fn($q) => $q->whereNull('removed_at'))
                ->count();

            if ($availableCount > 0) {
                $locations[] = [
                    'level' => $column->level,
                    'height' => $column->height,
                    'available' => $availableCount,
                    'type' => 'existing_sku'
                ];
            }
        }

        // 2. Reserved locations
        $reservedColumns = LocationReservation::where('product_id', $productId)->get();
        foreach ($reservedColumns as $reservation) {
            $availableCount = Location::where('level', $reservation->level)
                ->where('height', $reservation->height)
                ->whereDoesntHave('inventory', fn($q) => $q->whereNull('removed_at'))
                ->count();

            if ($availableCount > 0) {
                $locations[] = [
                    'level' => $reservation->level,
                    'height' => $reservation->height,
                    'available' => $availableCount,
                    'type' => 'reserved'
                ];
            }
        }

        // 3. Empty columns
        $reservedForOthers = LocationReservation::where('product_id', '!=', $productId)
            ->get()
            ->mapWithKeys(fn($r) => [$r->level . $r->height => true])
            ->toArray();

        $activeColumns = DB::table('locations')
            ->select('locations.level', 'locations.height')
            ->join('inventories', 'locations.id', '=', 'inventories.location_id')
            ->whereNull('inventories.removed_at')
            ->groupBy('locations.level', 'locations.height')
            ->get()
            ->mapWithKeys(fn($c) => [$c->level . $c->height => true])
            ->toArray();

        foreach (range('A', 'L') as $level) {
            foreach (range(1, 6) as $height) {
                $columnKey = $level . $height;
                if (!isset($reservedForOthers[$columnKey]) && !isset($activeColumns[$columnKey])) {
                    $locations[] = [
                        'level' => $level,
                        'height' => $height,
                        'available' => 50,
                        'type' => 'empty'
                    ];
                }
            }
        }

        return response()->json($locations);
    }

    // Bulk add items to specific locations
    public function storeBulkInbound(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.batch_id' => 'required|exists:batches,id',
            'items.*.level' => 'required|in:' . implode(',', range('A', 'L')),
            'items.*.height' => 'required|integer|between:1,6',
            'items.*.quantity' => 'required|integer|min:1|max:50'
        ]);

        $createdCount = 0;
        $errors = [];

        DB::transaction(function () use ($validated, &$createdCount, &$errors) {
            foreach ($validated['items'] as $index => $itemData) {
                try {
                    $product = Product::findOrFail($itemData['product_id']);
                    $batch = Batch::findOrFail($itemData['batch_id']);

                    // Get available locations in the specified column
                    $availableLocations = Location::where('level', $itemData['level'])
                        ->where('height', $itemData['height'])
                        ->whereDoesntHave('inventory', fn($q) => $q->whereNull('removed_at'))
                        ->orderByDesc('depth')
                        ->limit($itemData['quantity'])
                        ->get();

                    if ($availableLocations->count() < $itemData['quantity']) {
                        $errors[] = "Item {$index}: Not enough space in {$itemData['level']}{$itemData['height']}. Only {$availableLocations->count()} slots available.";
                        return;
                    }

                    foreach ($availableLocations as $location) {
                        $item = Item::create([
                            'batch_id' => $itemData['batch_id'],
                            'barcode' => 'BULK-' . time() . '-' . uniqid()
                        ]);

                        Inventory::create([
                            'item_id' => $item->id,
                            'location_id' => $location->id,
                            'placed_at' => now()->subHours(rand(0, 720)) // Random time up to 30 days ago
                        ]);

                        // Update location's current SKU if empty
                        if (!$location->current_sku) {
                            $location->update(['current_sku' => $product->sku]);
                        }

                        $createdCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Item {$index}: " . $e->getMessage();
                }
            }
        });

        return response()->json([
            'success' => $createdCount > 0,
            'created_count' => $createdCount,
            'errors' => $errors,
            'message' => $createdCount > 0 ? "{$createdCount} items added successfully" : 'No items were added'
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
