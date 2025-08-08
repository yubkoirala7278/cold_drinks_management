<?php

namespace App\Http\Controllers;

use App\Models\LocationReservation;
use App\Models\Product;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LocationReservationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $reservations = LocationReservation::with('product')->select('location_reservations.*');

            return DataTables::of($reservations)
                ->addIndexColumn()
                ->addColumn('location_code', function ($row) {
                    return $row->level . $row->height;
                })
                ->addColumn('product_name', function ($row) {
                    return $row->product->name;
                })
                ->addColumn('product_sku', function ($row) {
                    return $row->product->sku;
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex gap-2">';
                    $btn .= '<a href="' . route('location-reservations.edit', $row->id) . '" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>';
                    $btn .= '<button class="btn btn-danger btn-sm delete-btn" data-id="' . $row->id . '"><i class="fas fa-trash"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('location-reservations.index');
    }

    public function create()
    {
        $products = Product::all();
        $levels = range('A', 'L');
        $heights = range(1, 6);
        return view('location-reservations.create', compact('products', 'levels', 'heights'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'level' => 'required|in:A,B,C,D,E,F,G,H,I,J,K,L',
            'height' => 'required|integer|between:1,6',
        ]);

        // Check if location has items
        if ($this->locationHasItems($request->level, $request->height)) {
            return back()->with('error', 'Location contains items and cannot be reserved');
        }

        // Check if already reserved for another product
        $existing = LocationReservation::where('level', $request->level)
            ->where('height', $request->height)
            ->where('product_id', '!=', $request->product_id)
            ->first();

        if ($existing) {
            return back()->with('error', 'Location already reserved for another product');
        }

        // Create or update reservation
        LocationReservation::updateOrCreate(
            ['level' => $request->level, 'height' => $request->height],
            ['product_id' => $request->product_id]
        );

        return redirect()->route('location-reservations.index')
            ->with('success', 'Location reserved successfully');
    }

    public function edit(LocationReservation $locationReservation)
    {
        $products = Product::all();
        $levels = range('A', 'L');
        $heights = range(1, 6);
        return view('location-reservations.edit', compact('locationReservation', 'products', 'levels', 'heights'));
    }

    public function update(Request $request, LocationReservation $locationReservation)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'level' => 'required|in:A,B,C,D,E,F,G,H,I,J,K,L',
            'height' => 'required|integer|between:1,6',
        ]);

        // Check if new location has items
        if (($request->level != $locationReservation->level || $request->height != $locationReservation->height) &&
            $this->locationHasItems($request->level, $request->height)
        ) {
            return back()->with('error', 'New location contains items and cannot be reserved');
        }

        // Check if new location is reserved by another product
        $existing = LocationReservation::where('level', $request->level)
            ->where('height', $request->height)
            ->where('id', '!=', $locationReservation->id)
            ->first();

        if ($existing) {
            return back()->with('error', 'New location already reserved for another product');
        }

        $locationReservation->update($request->all());

        return redirect()->route('location-reservations.index')
            ->with('success', 'Reservation updated successfully');
    }

    public function destroy(LocationReservation $locationReservation)
    {
        // Check if location now has items
        if ($this->locationHasItems($locationReservation->level, $locationReservation->height)) {
            return response()->json([
                'error' => 'Cannot delete reservation - location now contains items'
            ], 422);
        }

        $locationReservation->delete();

       return response()->json([
            'success' => 'Location reservation deleted successfully'
        ]);
    }

    protected function locationHasItems($level, $height)
    {
        return DB::table('inventories')
            ->join('locations', 'inventories.location_id', '=', 'locations.id')
            ->where('locations.level', $level)
            ->where('locations.height', $height)
            ->whereNull('inventories.removed_at')
            ->exists();
    }
}
