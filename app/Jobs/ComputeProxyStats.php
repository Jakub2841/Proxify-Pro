<?php

namespace App\Jobs;

use App\Models\Proxy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class ComputeProxyStats implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cache::put('proxy-stats', [
            ['label' => __('Tracked'), 'value' => number_format(Proxy::count())],
            ['label' => __('Passing now'), 'value' => number_format(Proxy::where('is_active', true)->where('google_pass', true)->count()), 'accent' => true],
            ['label' => __('Avg latency'), 'value' => round(Proxy::whereNotNull('latency_ms')->avg('latency_ms') ?? 0).' ms'],
            ['label' => __('Active sources'), 'value' => number_format(Proxy::where('is_active', true)->count())],
        ], now()->addMinutes(2));
    }
}
