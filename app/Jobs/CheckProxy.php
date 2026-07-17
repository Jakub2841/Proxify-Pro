<?php

namespace App\Jobs;

use App\Models\Proxy;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class CheckProxy implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 10;

    public int $timeout;

    private int $maxLatencySec;

    public function __construct(public Proxy $proxy)
    {
        $maxLatencyMs = Setting::get('max_latency', 2000);
        $this->maxLatencySec = max(1, (int) ceil($maxLatencyMs / 1000));
        $this->timeout = $this->maxLatencySec + 8;
    }

    public function handle(): void
    {

        $start = microtime(true);
        $googlePass = false;
        $cloudflarePass = false;

        $responses = Http::pool(fn ($pool) => [
            $pool->withOptions([
                'proxy' => $this->proxy->protocol->value.'://'.$this->proxy->address.':'.$this->proxy->port,
            ])
                ->connectTimeout(0.5)
                ->timeout($this->maxLatencySec)
                ->get('http://www.google.com'),

            $pool->withOptions([
                'proxy' => $this->proxy->protocol->value.'://'.$this->proxy->address.':'.$this->proxy->port,
            ])
                ->connectTimeout(0.5)
                ->timeout($this->maxLatencySec)
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
                'proxy' => $this->proxy->protocol->value.'://'.$this->proxy->address.':'.$this->proxy->port,
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
