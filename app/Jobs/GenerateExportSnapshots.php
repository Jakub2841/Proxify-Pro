<?php

namespace App\Jobs;

use App\Enums\Protocol;
use App\Models\Proxy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateExportSnapshots implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $activeCount = Proxy::where('is_active', true)->count();

        Log::info('GenerateExportSnapshots: starting', ['active_proxies' => $activeCount]);

        $disk = Storage::disk('public');
        $disk->makeDirectory('proxies');

        $activeQuery = Proxy::where('is_active', true);

        // ── all.txt ──────────────────────────────────────────────────────
        $this->atomicWrite($disk, 'proxies/all.txt', function ($handle) use ($activeQuery) {
            (clone $activeQuery)->chunkById(500, function ($proxies) use ($handle) {
                foreach ($proxies as $proxy) {
                    fwrite($handle, $proxy->protocol->value.'://'.$proxy->address.':'.$proxy->port.PHP_EOL);
                }
            });
        });

        // ── all.csv ──────────────────────────────────────────────────────
        $this->atomicWrite($disk, 'proxies/all.csv', function ($handle) use ($activeQuery) {
            (clone $activeQuery)->chunkById(500, function ($proxies) use ($handle) {
                foreach ($proxies as $proxy) {
                    fputcsv($handle, [$proxy->address.':'.$proxy->port]);
                }
            });
        });

        // ── all.json ─────────────────────────────────────────────────────
        $this->atomicWrite($disk, 'proxies/all.json', function ($handle) use ($activeQuery) {
            fwrite($handle, '[');
            $first = true;

            (clone $activeQuery)->chunkById(500, function ($proxies) use ($handle, &$first) {
                foreach ($proxies as $proxy) {
                    if (! $first) {
                        fwrite($handle, ',');
                    }
                    $first = false;

                    fwrite($handle, json_encode([
                        'address' => $proxy->address.':'.$proxy->port,
                        'protocol' => $proxy->protocol->value,
                    ], JSON_UNESCAPED_SLASHES));
                }
            });

            fwrite($handle, ']');
        });

        // ── Per-protocol .txt ────────────────────────────────────────────
        foreach (Protocol::cases() as $protocol) {
            $this->atomicWrite($disk, 'proxies/'.$protocol->value.'.txt', function ($handle) use ($activeQuery, $protocol) {
                (clone $activeQuery)->where('protocol', $protocol->value)->chunkById(500, function ($proxies) use ($handle, $protocol) {
                    foreach ($proxies as $proxy) {
                        fwrite($handle, $protocol->value.'://'.$proxy->address.':'.$proxy->port.PHP_EOL);
                    }
                });
            });
        }

        Log::info('GenerateExportSnapshots: complete', ['active_proxies' => $activeCount]);
    }

    /**
     * Write content to a temporary file, then atomically rename over the target.
     */
    private function atomicWrite($disk, string $path, callable $callback): void
    {
        $tmpPath = $path.'.tmp';

        $handle = fopen($disk->path($tmpPath), 'w');
        $callback($handle);
        fclose($handle);

        $disk->move($tmpPath, $path);
    }
}
