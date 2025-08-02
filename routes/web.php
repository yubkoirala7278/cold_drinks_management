<?php

use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// routes/web.php
Route::prefix('warehouse')->group(function () {
    Route::get('/', [WarehouseController::class, 'dashboard'])->name('warehouse.dashboard');
    Route::get('/inbound', [WarehouseController::class, 'inbound'])->name('warehouse.inbound');
    Route::get('/batches/{product}', [WarehouseController::class, 'getBatches']);
    Route::post('/find-location', [WarehouseController::class, 'findLocation']);
    Route::post('/store-item', [WarehouseController::class, 'storeItem']);
    Route::get('/outbound', [WarehouseController::class, 'outbound'])->name('warehouse.outbound');
    Route::post('/next-item', [WarehouseController::class, 'getNextItem']);
    Route::post('/remove-item', [WarehouseController::class, 'removeItem']);
});
