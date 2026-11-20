<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Public authentication endpoint.
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('api.login');

Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:5,1')
    ->name('api.password.email');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:5,1')
    ->name('api.password.reset');

// All routes in this group require a valid Sanctum bearer token.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::put('/password', [AuthController::class, 'changePassword'])
        ->middleware('throttle:5,1')
        ->name('api.password.change');
    Route::put('/update-profile', [AuthController::class, 'updateProfile'])->name('api.update-profile');

    Route::get('/categories', [CategoryController::class, 'index'])->name('api.categories.index');
    Route::get('/products/summary', [ProductController::class, 'summary'])->name('api.products.summary');
    Route::get('/products', [ProductController::class, 'index'])->name('api.products.index');
    Route::get('/products/export', [ProductController::class, 'export'])
        ->middleware('role:admin')
        ->name('api.products.export');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('api.products.show');
    Route::middleware('role:admin')->group(function () {
        Route::post('/categories', [CategoryController::class, 'store'])->name('api.categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('api.categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
            ->name('api.categories.destroy');
        Route::post('/products', [ProductController::class, 'store'])->name('api.products.store');
        Route::post('/products/import', [ProductController::class, 'import'])
            ->middleware('throttle:3,1')
            ->name('api.products.import');
        Route::match(['put', 'patch'], '/products/{product}', [ProductController::class, 'update'])
            ->name('api.products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('api.products.destroy');
    });
    // Route::apiResource('products', ProductController::class);
});
