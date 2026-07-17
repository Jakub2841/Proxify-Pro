<?php

namespace App\Jobs;

use App\Models\Proxy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class CheckProxies implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public int $tries = 1;

    public function handle(): void
    {
        Cache::put('checking', true, 600);

        foreach (Proxy::stale()->cursor() as $proxy) {
            CheckProxy::dispatch($proxy);
        }
    }
}
