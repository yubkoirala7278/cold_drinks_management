<?php

use App\Http\Controllers\OutboundController;
use Illuminate\Support\Facades\Route;

// Protected Warehouse Routes
Route::prefix('warehouse')->middleware('auth')->group(function () {
    // Outbound routes (accessible by admin and outbound_staff)
    Route::middleware('role:outbound_staff')->group(function () {
        Route::get('/outbound', [OutboundController::class, 'outbound'])->name('warehouse.outbound');
        Route::post('/next-item', [OutboundController::class, 'getNextItem']);
        Route::post('/remove-item', [OutboundController::class, 'removeItem']);
    });
});
