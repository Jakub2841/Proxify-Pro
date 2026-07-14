<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::view('/dashboard', 'welcome')->name('dashboard');
Route::view('/proxies', 'welcome')->name('proxies.index');
Route::view('/sources', 'welcome')->name('sources.index');
Route::view('/exports', 'welcome')->name('exports.index');
Route::view('/api-access', 'welcome')->name('api-access');
Route::view('/settings', 'welcome')->name('settings');
