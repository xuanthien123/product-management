<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::view('/login', 'auth.login')->name('login');
Route::view('/dashboard', 'products.dashboard')->name('dashboard');

Route::view('/products', 'products.index')->name('products.index');
Route::view('/products/create', 'products.create')->name('products.create');
// Route::view('/products/{product}', 'products.show')->name('products.show');
Route::view('/products/{product}/edit', 'products.edit')->name('products.edit');
