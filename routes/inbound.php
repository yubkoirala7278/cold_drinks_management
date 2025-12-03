<?php

use App\Http\Controllers\InboundController;
use App\Http\Controllers\WarehouseMatrixController;
use Illuminate\Support\Facades\Route;

// Protected Warehouse Routes
Route::prefix('warehouse')->middleware('auth')->group(function () {

    // Inbound routes (accessible by admin and inbound_staff)
    Route::middleware('role:inbound_staff')->group(function () {
        Route::get('/inbound', [InboundController::class, 'inbound'])->name('warehouse.inbound');
        Route::get('/batches/{product}', [InboundController::class, 'getBatches']);
        Route::post('/find-location', [InboundController::class, 'findLocation']);
        Route::post('/store-item', [InboundController::class, 'storeItem']);
    });

    // Matrix Dashboard (accessible by all authenticated users)
    Route::get('/matrix', [WarehouseMatrixController::class, 'dashboard'])->name('warehouse.matrix');
});

// API Routes for matrix data (no auth required for display - can be restricted later)
Route::prefix('api/warehouse')->group(function () {
    Route::get('/matrix-data', [WarehouseMatrixController::class, 'getMatrixData']);
    Route::get('/summary', [WarehouseMatrixController::class, 'getSummary']);
});
