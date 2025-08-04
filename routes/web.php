<?php

use App\Http\Controllers\InboundController;
use App\Http\Controllers\OutboundController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// routes/web.php
Route::prefix('warehouse')->group(function () {
    Route::get('/', [WarehouseController::class, 'dashboard'])->name('warehouse.dashboard');
    // Inbound
    Route::get('/inbound', [InboundController::class, 'inbound'])->name('warehouse.inbound');
    Route::get('/batches/{product}', [InboundController::class, 'getBatches']);
    Route::post('/find-location', [InboundController::class, 'findLocation']);
    Route::post('/store-item', [InboundController::class, 'storeItem']);
    // Outbound
    Route::get('/outbound', [OutboundController::class, 'outbound'])->name('warehouse.outbound');
    Route::post('/next-item', [OutboundController::class, 'getNextItem']);
    Route::post('/remove-item', [OutboundController::class, 'removeItem']);
});
