<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseMatrixController extends Controller
{
    /**
     * Display warehouse matrix dashboard
     */
    public function dashboard()
    {
        return view('warehouse.matrix-dashboard');
    }

    /**
     * Get matrix data (levels x heights with SKU info)
     * Returns active inventory count and SKU for each column (level+height)
     */
    public function getMatrixData(Request $request)
    {
        // Get all unique level+height combinations with their current SKU and inventory count
        $matrix = DB::table('locations')
            ->select(
                'locations.level',
                'locations.height',
                'locations.current_sku',
                DB::raw('COUNT(CASE WHEN inventories.removed_at IS NULL THEN 1 END) as active_count'),
                DB::raw('COUNT(CASE WHEN inventories.id IS NOT NULL THEN 1 END) as total_count')
            )
            ->leftJoin('inventories', 'locations.id', '=', 'inventories.location_id')
            ->groupBy('locations.level', 'locations.height', 'locations.current_sku')
            ->orderBy('locations.level')
            ->orderBy('locations.height')
            ->get();

        // Get product info (SKU -> name mapping)
        $products = DB::table('products')
            ->select('sku', 'name')
            ->get()
            ->keyBy('sku')
            ->toArray();

        // Transform into matrix structure: level -> height -> {sku, active_count, product_name}
        $matrixData = [];
        foreach (range('A', 'L') as $level) {
            $matrixData[$level] = [];
            foreach (range(1, 6) as $height) {
                $matrixData[$level][$height] = [
                    'level' => $level,
                    'height' => $height,
                    'sku' => null,
                    'product_name' => 'EMPTY',
                    'active_count' => 0,
                    'total_count' => 0,
                    'capacity' => 50,
                    'occupancy_percent' => 0,
                    'status' => 'empty' // empty, partial, full
                ];
            }
        }

        // Fill matrix with actual data
        foreach ($matrix as $row) {
            $level = $row->level;
            $height = $row->height;
            $sku = $row->current_sku;

            if ($sku) {
                $productName = isset($products[$sku]) ? $products[$sku]->name : $sku;
                $occupancyPercent = round(($row->active_count / 50) * 100);

                $status = 'empty';
                if ($row->active_count > 0 && $row->active_count < 50) {
                    $status = 'partial';
                } elseif ($row->active_count >= 50) {
                    $status = 'full';
                }

                $matrixData[$level][$height] = [
                    'level' => $level,
                    'height' => $height,
                    'sku' => $sku,
                    'product_name' => $productName,
                    'active_count' => (int)$row->active_count,
                    'total_count' => (int)$row->total_count,
                    'capacity' => 50,
                    'occupancy_percent' => $occupancyPercent,
                    'status' => $status
                ];
            }
        }

        // Convert to array format for JSON response
        $response = [];
        foreach ($matrixData as $level => $heightData) {
            $response[] = [
                'level' => $level,
                'heights' => array_values($heightData)
            ];
        }

        return response()->json([
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'matrix' => $response
        ]);
    }

    /**
     * Get summary statistics for the dashboard
     */
    public function getSummary()
    {
        $summary = DB::table('inventories')
            ->select(
                DB::raw('COUNT(CASE WHEN removed_at IS NULL THEN 1 END) as active_items'),
                DB::raw('COUNT(CASE WHEN removed_at IS NOT NULL THEN 1 END) as removed_items'),
                DB::raw('COUNT(*) as total_items')
            )
            ->first();

        $totalCapacity = 12 * 6 * 50; // 3600 slots
        $occupancyPercent = round(($summary->active_items / $totalCapacity) * 100);

        return response()->json([
            'active_items' => (int)$summary->active_items,
            'removed_items' => (int)$summary->removed_items,
            'total_capacity' => $totalCapacity,
            'occupancy_percent' => $occupancyPercent,
            'available_slots' => $totalCapacity - $summary->active_items
        ]);
    }
}
