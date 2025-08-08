<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\InboundController;
use App\Http\Controllers\LocationReservationController;
use App\Http\Controllers\OutboundController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('admin.login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Warehouse Routes
Route::prefix('warehouse')->middleware('auth')->group(function () {
    // Admin dashboard (accessible only by admin)
    Route::get('/', [WarehouseController::class, 'dashboard'])
        ->name('warehouse.dashboard')
        ->middleware('role:admin');

    // Inbound routes (accessible by admin and inbound_staff)
    Route::middleware('role:inbound_staff')->group(function () {
        Route::get('/inbound', [InboundController::class, 'inbound'])->name('warehouse.inbound');
        Route::get('/batches/{product}', [InboundController::class, 'getBatches']);
        Route::post('/find-location', [InboundController::class, 'findLocation']);
        Route::post('/store-item', [InboundController::class, 'storeItem']);
    });

    // Outbound routes (accessible by admin and outbound_staff)
    Route::middleware('role:outbound_staff')->group(function () {
        Route::get('/outbound', [OutboundController::class, 'outbound'])->name('warehouse.outbound');
        Route::post('/next-item', [OutboundController::class, 'getNextItem']);
        Route::post('/remove-item', [OutboundController::class, 'removeItem']);
    });
});

Route::resource('products', ProductController::class);
Route::resource('batches', BatchController::class);

// routes/web.php
Route::prefix('barcodes')->group(function () {
    Route::get('/', [BarcodeController::class, 'index'])->name('barcodes.generate');
    Route::post('/', [BarcodeController::class, 'generate'])->name('barcodes.generate.post');
    Route::get('/export-pdf', [BarcodeController::class, 'exportPdf'])->name('barcodes.export.pdf');
});


Route::resource('location-reservations', LocationReservationController::class)
    ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);