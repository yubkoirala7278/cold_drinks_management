<?php

use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\LocationReservationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

// Protected Warehouse Routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/warehouse', [WarehouseController::class, 'dashboard'])->name('warehouse.dashboard');

    // Multiple resource routes
    Route::resources([
        'products' => ProductController::class,
        'batches' => BatchController::class,
        'location-reservations' => LocationReservationController::class,
    ]);

    Route::prefix('barcodes')->group(function () {
        Route::get('/', [BarcodeController::class, 'index'])->name('barcodes.generate');
        Route::post('/', [BarcodeController::class, 'generate'])->name('barcodes.generate.post');
        Route::get('/export-pdf', [BarcodeController::class, 'exportPdf'])->name('barcodes.export.pdf');
    });
});
