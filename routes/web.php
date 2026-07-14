<?php

use App\Http\Controllers\ProxiesController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProxiesController::class, 'index'])->name('home');

Route::get('/dashboard', [ProxiesController::class, 'index'])->name('dashboard');
Route::get('/proxies', [ProxiesController::class, 'index'])->name('proxies.index');
Route::view('/sources', 'layouts.app')->name('sources.index');
Route::view('/exports', 'layouts.app')->name('exports.index');
Route::view('/api-access', 'layouts.app')->name('api-access');
Route::view('/settings', 'layouts.app')->name('settings');
