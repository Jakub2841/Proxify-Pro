<?php

use App\Livewire\ProxiesIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', ProxiesIndex::class)->name('home');

Route::get('/dashboard', ProxiesIndex::class)->name('dashboard');
Route::view('/sources', 'layouts.app')->name('sources.index');
Route::view('/exports', 'layouts.app')->name('exports.index');
Route::view('/api-access', 'layouts.app')->name('api-access');
Route::view('/settings', 'layouts.app')->name('settings');
