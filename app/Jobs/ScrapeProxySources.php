<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ScrapeProxySources implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    public function handle(): void
    {
        Log::info('ScrapeProxySources: starting');
        // TODO: implement scraping logic
        Log::info('ScrapeProxySources: complete');
    }
}
