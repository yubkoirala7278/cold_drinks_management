<?php

use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\LocationReservationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

// Protected Warehouse Routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/warehouse', [WarehouseController::class, 'dashboard'])->name('warehouse.dashboard');

    // profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');
    
    // Multiple resource routes
    Route::post('users/{user}/change-password', [UserController::class, 'changePassword'])
        ->name('users.change-password');
    Route::resources([
        'users' => UserController::class,
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
