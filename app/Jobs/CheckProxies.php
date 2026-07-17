<?php

namespace App\Jobs;

use App\Models\Proxy;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class CheckProxies implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public int $tries = 1;

    public int $uniqueFor = 60;

    public function uniqueId(): string
    {
        return 'check-proxies';
    }

    public function handle(): void
    {
        Cache::put('checking', true, 600);

        try {
            foreach (Proxy::stale()->cursor() as $proxy) {
                CheckProxy::dispatch($proxy);
            }
        } finally {
            Cache::forget('checking');
        }
    }
}
