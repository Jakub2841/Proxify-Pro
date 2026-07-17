<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckProxies implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    public function handle(): void
    {
        Log::info('CheckProxies: starting');
        // TODO: implement proxy checking logic
        Log::info('CheckProxies: complete');
    }
}
