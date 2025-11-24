<?php

namespace App\Http\Controllers;

use App\Models\{Inventory, Item, Location, Product};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OutboundController extends Controller
{
    /**
     * Display outbound page with statistics
     */
    public function outbound()
    {
        return view('warehouse.outbound', [
            'products' => Product::orderBy('name')->get(),
            'todayOutboundCount' => $this->getTodayOutboundCount(),
            'topProducts' => $this->getTopPickedProducts()
        ]);
    }

    /**
     * Get next item for picking (FIFO)
     */
    public function getNextItem(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        try {
            $inventory = $this->findOldestAvailableItem($request->product_id);

            if (!$inventory) {
                return response()->json([
                    'error' => 'No items available for this product',
                    'no_items' => true
                ], 200);
            }

            return $this->formatPickResponse($inventory);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to find next item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm item removal
     */
    public function removeItem(Request $request)
    {
        $validated = $request->validate([
            // Barcode may be the system outbound barcode (e.g. '11111111') or the real
            // item barcode. Do not require existence in items table here because the
            // system barcode will not exist as an item barcode.
            'barcode' => 'required',
            'location_id' => 'required|exists:locations,id'
        ]);

        DB::transaction(function () use ($validated) {
            $inventory = $this->validatePickRequest($validated);
            $this->markItemAsPicked($inventory);
            $this->shiftItemsForward($inventory->location);
        });

        return response()->json([
            'success' => true,
            'stats' => [
                'today_outbound' => $this->getTodayOutboundCount()
            ]
        ]);
    }

    // ========== PRIVATE METHODS ========== //

    private function getTodayOutboundCount()
    {
        return Inventory::whereDate('removed_at', today())->count();
    }

    private function getTopPickedProducts()
    {
        return DB::table('inventories')
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('count(*) as count')
            )
            ->join('items', 'inventories.item_id', '=', 'items.id')
            ->join('batches', 'items.batch_id', '=', 'batches.id')
            ->join('products', 'batches.product_id', '=', 'products.id')
            ->whereDate('removed_at', today())
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();
    }

    private function findOldestAvailableItem($productId)
    {
        return Inventory::whereHas('item.batch', fn($q) => $q->where('product_id', $productId))
            ->whereNull('removed_at')
            ->with(['item.batch.product', 'location'])
            ->orderBy('placed_at')
            ->first();
    }

    private function formatPickResponse($inventory)
    {
        $isLastInLocation = $this->isLastItemInLocation($inventory->location);

        return [
            'location' => $this->formatLocationCode($inventory->location),
            'barcode' => $inventory->item->barcode,
            'location_id' => $inventory->location_id,
            'is_last_in_location' => $isLastInLocation,
            'product_name' => $inventory->item->batch->product->name
        ];
    }

    private function isLastItemInLocation($location)
    {
        return Inventory::where('location_id', $location->id)
            ->whereNull('removed_at')
            ->count() === 1;
    }

    private function validatePickRequest($validated)
    {
        $outboundCode = config('warehouse.outbound_barcode');

        // If the scanned barcode equals the system outbound barcode, identify the
        // inventory by the provided location_id (UI supplies the location for the
        // picked item). Otherwise, find the inventory by the scanned barcode + location.
        if ($validated['barcode'] === $outboundCode) {
            return Inventory::with(['location', 'item'])
                ->where('location_id', $validated['location_id'])
                ->whereNull('removed_at')
                ->firstOrFail();
        }

        return Inventory::with(['location', 'item'])
            ->whereHas('item', fn($q) => $q->where('barcode', $validated['barcode']))
            ->where('location_id', $validated['location_id'])
            ->whereNull('removed_at')
            ->firstOrFail();
    }

    private function markItemAsPicked($inventory)
    {
        $inventory->update([
            'removed_at' => Carbon::now(),
        ]);
    }

    private function shiftItemsForward(Location $location)
    {
        // Get all items in the same column that need shifting
        $itemsToShift = Inventory::select('inventories.*')
            ->join('locations', 'inventories.location_id', '=', 'locations.id')
            ->where('locations.level', $location->level)
            ->where('locations.height', $location->height)
            ->where('locations.depth', '<', $location->depth)
            ->whereNull('inventories.removed_at')
            ->orderByDesc('locations.depth')
            ->with('location')
            ->get();

        foreach ($itemsToShift as $inventory) {
            $newDepth = $inventory->location->depth + 1;

            $newLocation = Location::where('level', $location->level)
                ->where('height', $location->height)
                ->where('depth', $newDepth)
                ->first();

            if ($newLocation) {
                $inventory->update(['location_id' => $newLocation->id]);
            }
        }

        // Check if the column is now empty
        $this->clearSkuIfColumnEmpty($location);
    }

    private function clearSkuIfColumnEmpty(Location $location)
    {
        $hasItems = Inventory::whereHas('location', function ($q) use ($location) {
            $q->where('level', $location->level)
                ->where('height', $location->height);
        })
            ->whereNull('removed_at')
            ->exists();

        if (!$hasItems) {
            // Clear SKU assignment for the entire column
            Location::where('level', $location->level)
                ->where('height', $location->height)
                ->update(['current_sku' => null]);
        }
    }

    private function formatLocationCode($location)
    {
        return sprintf(
            '%s%s-S%02d',
            $location->level,
            $location->height,
            $location->depth
        );
    }
}
