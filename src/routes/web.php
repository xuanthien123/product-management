<?php

use App\Http\Controllers\ProductImportTemplateController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::view('/login', 'auth.login')->name('login');
Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
Route::view('/reset-password', 'auth.reset-password')->name('password.reset');
Route::view('/dashboard', 'products.dashboard')->name('dashboard');

Route::view('/products', 'products.index')->name('products.index');
Route::view('/products/create', 'products.create')->name('products.create');
Route::view('/products/import', 'products.import')->name('products.import');
Route::get('/templates/products-import-template.xlsm', [ProductImportTemplateController::class, 'download'])
    ->name('products.import-template');
// Route::view('/products/{product}', 'products.show')->name('products.show');
Route::view('/products/{product}/edit', 'products.edit')->name('products.edit');

Route::view('/profile', 'auth.profile')->name('profile');
Route::view('/profile/edit', 'auth.edit-profile')->name('profile.edit');
Route::view('/change-password', 'auth.change-password')->name('change-password');
