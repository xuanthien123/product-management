<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Public authentication endpoint.
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('api.login');

// All routes in this group require a valid Sanctum bearer token.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    Route::get('/products/summary', [ProductController::class, 'summary'])->name('api.products.summary');
    Route::get('/products', [ProductController::class, 'index'])->name('api.products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('api.products.show');
    Route::middleware('role:admin')->group(function () {
        Route::post('/products', [ProductController::class, 'store'])->name('api.products.store');
        Route::match(['put', 'patch'], '/products/{product}', [ProductController::class, 'update'])
            ->name('api.products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('api.products.destroy');
    });
    // Route::apiResource('products', ProductController::class);
});
