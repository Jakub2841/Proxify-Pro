<?php

use App\Livewire\ProxiesIndex;
use App\Livewire\SourcesIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', ProxiesIndex::class)->name('home');

Route::get('/dashboard', ProxiesIndex::class)->name('dashboard');
Route::get('/sources', SourcesIndex::class)->name('sources.index');
Route::view('/api-access', 'layouts.app')->name('api-access');
Route::view('/settings', 'layouts.app')->name('settings');
