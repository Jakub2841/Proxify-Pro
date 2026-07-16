<?php

use App\Http\Controllers\Api\ProxyController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/proxies', [ProxyController::class, 'index']);
    Route::get('/proxies/txt', [ProxyController::class, 'txt']);
});
