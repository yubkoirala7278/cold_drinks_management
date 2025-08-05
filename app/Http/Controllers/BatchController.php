<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Batch::with('product')->select('batches.*');

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('product_name', function ($row) {
                    return $row->product->name;
                })
                ->addColumn('product_sku', function ($row) {
                    return $row->product->sku;
                })
                ->addColumn('production_date_formatted', function ($row) {
                    return Carbon::parse($row->production_date)->format('d M Y');
                })
                ->addColumn('expiry_date_formatted', function ($row) {
                    return Carbon::parse($row->expiry_date)->format('d M Y');
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex gap-2">';
                    $btn .= '<a href="' . route('batches.show', $row->id) . '" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>';
                    $btn .= '<a href="' . route('batches.edit', $row->id) . '" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>';
                    $btn .= '<button class="btn btn-danger btn-sm delete-btn" data-id="' . $row->id . '"><i class="fas fa-trash"></i></button>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('batches.index');
    }

    public function create()
    {
        $products = Product::all();
        return view('batches.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'batch_number' => 'required|string|max:50',
            'production_date' => 'required|date',
            'expiry_date' => 'required|date|after:production_date',
        ]);

        // Check for unique batch number per product
        if (Batch::where('product_id', $request->product_id)
            ->where('batch_number', $request->batch_number)
            ->exists()
        ) {
            return back()->withInput()->with('error', 'This batch number already exists for the selected product');
        }

        Batch::create($request->all());

        return redirect()->route('batches.index')
            ->with('success', 'Batch created successfully.');
    }

    public function show(Batch $batch)
    {
        return view('batches.show', compact('batch'));
    }

    public function edit(Batch $batch)
    {
        $products = Product::all();
        return view('batches.edit', compact('batch', 'products'));
    }

    public function update(Request $request, Batch $batch)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'batch_number' => 'required|string|max:50',
            'production_date' => 'required|date',
            'expiry_date' => 'required|date|after:production_date',
        ]);

        // Check for unique batch number per product (excluding current batch)
        if (Batch::where('product_id', $request->product_id)
            ->where('batch_number', $request->batch_number)
            ->where('id', '!=', $batch->id)
            ->exists()
        ) {
            return back()->withInput()->with('error', 'This batch number already exists for the selected product');
        }

        $batch->update($request->all());

        return redirect()->route('batches.index')
            ->with('success', 'Batch updated successfully');
    }

    public function destroy(Batch $batch)
    {
        // Check if batch is used in any relations (e.g., items)
        if ($batch->items()->exists()) {
            return response()->json([
                'error' => 'Cannot delete batch. It is being used in items.'
            ], 422);
        }

        $batch->delete();

        return response()->json([
            'success' => 'Batch deleted successfully'
        ]);
    }
}
