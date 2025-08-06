<?php

// app/Http/Controllers/BarcodeController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BarcodeTracker;
use Barryvdh\DomPDF\Facade\Pdf;
use Milon\Barcode\Facades\DNS1DFacade;

class BarcodeController extends Controller
{
    public function index()
    {
        $tracker = BarcodeTracker::firstOrCreate([], ['last_number' => 0]);
        $nextNumber = $tracker->last_number + 1;

        return view('barcodes.generate', [
            'lastNumber' => $tracker->last_number,
            'nextNumber' => $nextNumber
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:10000',
        ]);

        $tracker = BarcodeTracker::firstOrCreate([], ['last_number' => 0]);
        $prefix = $tracker->prefix;
        $start = $tracker->last_number + 1;
        $end = $start + $request->quantity - 1;

        // Generate barcodes
        $barcodes = [];
        for ($i = $start; $i <= $end; $i++) {
            $number = str_pad($i, 8, '0', STR_PAD_LEFT);
            $code = $prefix . $number;
            $barcode = DNS1DFacade::getBarcodeHTML($code, 'C128', 2, 60);

            $barcodes[] = [
                'number' => $number,
                'code' => $code,
                'barcode' => $barcode
            ];
        }

        // Update the tracker immediately
        $tracker->update(['last_number' => $end]);

        // Store in session for PDF export
        $request->session()->put('barcodes', $barcodes);

        return view('barcodes.preview', compact('barcodes', 'start', 'end'));
    }

    public function exportPdf(Request $request)
    {
        if (!$request->session()->has('barcodes')) {
            return redirect()->route('barcodes.generate');
        }

        $barcodes = $request->session()->get('barcodes');
        $pdf = Pdf::loadView('barcodes.pdf', compact('barcodes'));

        // Clear the session after export
        $request->session()->forget('barcodes');

        return $pdf->download('barcodes-' . now()->format('Ymd-His') . '.pdf');
    }
}
