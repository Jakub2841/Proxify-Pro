<?php

use App\Http\Controllers\ExportProxiesController;
use App\Livewire\ProxiesIndex;
use App\Livewire\SettingsIndex;
use App\Livewire\SourcesIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', ProxiesIndex::class)->name('home');

Route::get('/dashboard', ProxiesIndex::class)->name('dashboard');
Route::get('/sources', SourcesIndex::class)->name('sources.index');
Route::get('/export/proxies/{format}', ExportProxiesController::class)
    ->where('format', 'csv|txt|json')
    ->middleware('throttle:10,1')
    ->name('export.proxies');
Route::view('/api-access', 'api-access')->name('api-access');
Route::get('/settings', SettingsIndex::class)->name('settings');
