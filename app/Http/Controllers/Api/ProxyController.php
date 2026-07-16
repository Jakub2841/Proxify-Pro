<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proxy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProxyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $proxies = Proxy::query()
            ->filtered($this->filterParams($request))
            ->latest('last_checked_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => $proxies->map(fn (Proxy $proxy) => [
                'address' => $proxy->address,
                'port' => $proxy->port,
                'protocol' => $proxy->protocol->value,
                'anonymity' => $proxy->anonymity?->value,
                'country' => $proxy->country,
                'google_pass' => $proxy->google_pass,
                'cloudflare_pass' => $proxy->cloudflare_pass,
                'latency_ms' => $proxy->latency_ms,
                'last_checked_at' => $proxy->last_checked_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $proxies->currentPage(),
                'last_page' => $proxies->lastPage(),
                'per_page' => $proxies->perPage(),
                'total' => $proxies->total(),
            ],
        ]);
    }

    public function txt(Request $request): StreamedResponse
    {
        $query = Proxy::query()->where('is_active', true)
            ->filtered($this->filterParams($request));

        return response()->stream(function () use ($query) {
            $query->chunkById(500, function ($proxies) {
                foreach ($proxies as $proxy) {
                    echo $proxy->protocol->value.'://'.$proxy->address.':'.$proxy->port.PHP_EOL;
                }
            });
        }, 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * @return array<string, mixed>
     */
    private function filterParams(Request $request): array
    {
        return [
            'protocols' => $request->query('protocols', []),
            'anonymity' => $request->query('anonymity', []),
            'checks' => $request->query('checks', []),
            'country' => $request->query('country', ''),
            'active_only' => $request->boolean('active_only'),
            'search' => '',
        ];
    }
}
