<?php

namespace App\Jobs;

use App\Models\Proxy;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckProxy implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 10;

    public int $timeout;

    public int $uniqueFor = 120;

    public function __construct(public Proxy $proxy)
    {
        $maxLatencyMs = Setting::get('max_latency', 2000);
        $this->timeout = max(1, (int) ceil($maxLatencyMs / 1000)) + 8;
    }

    public function uniqueId(): string
    {
        return 'check-proxy-'.$this->proxy->id;
    }

    public function handle(): void
    {
        $maxLatencyMs = Setting::get('max_latency', 2000);
        $maxLatencySec = max(1, (int) ceil($maxLatencyMs / 1000));

        $start = microtime(true);
        $uri = $this->proxy->connectionUri();

        $responses = Http::pool(fn ($pool) => [
            $pool->withOptions(['proxy' => $uri])
                ->connectTimeout(0.5)
                ->timeout($maxLatencySec)
                ->get('http://www.google.com'),

            $pool->withOptions(['proxy' => $uri])
                ->connectTimeout(0.5)
                ->timeout($maxLatencySec)
                ->get('https://www.cloudflare.com'),
        ]);

        $googlePass = $this->isPass($responses[0] ?? null);
        $cloudflarePass = $this->isPass($responses[1] ?? null);

        $latency = (int) round((microtime(true) - $start) * 1000);
        $country = $this->lookupCountry();

        $this->proxy->update([
            'google_pass' => $googlePass,
            'cloudflare_pass' => $cloudflarePass,
            'last_checked_at' => now(),
            'latency_ms' => $latency,
            'country' => $country,
        ]);

        if (! $googlePass && ! $cloudflarePass && Setting::get('save_inactive', false)) {
            $this->proxy->delete();
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CheckProxy failed', [
            'proxy_id' => $this->proxy->id,
            'address' => $this->proxy->address,
            'error' => $exception->getMessage(),
        ]);
    }

    private function isPass(mixed $response): bool
    {
        if (! $response instanceof Response) {
            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $body = $response->body();

        return ! str_contains($body, 'captcha') && ! str_contains($body, 'recaptcha');
    }

    private function lookupCountry(): ?string
    {
        try {
            $response = Http::withOptions([
                'proxy' => $this->proxy->connectionUri(),
            ])
                ->connectTimeout(0.5)
                ->timeout(5)
                ->get('http://ip-api.com/json');

            if ($response->successful()) {
                return $response->json('countryCode');
            }
        } catch (\Throwable) {
            //
        }

        return null;
    }
}
