<?php

namespace App\Http\Controllers;

use App\Enums\Check;
use App\Enums\Protocol;
use App\Enums\SortOption;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use League\ISO3166\ISO3166;

class ProxiesController extends Controller
{
    public function index(): View
    {
        return view('proxies.index', [
            'stats' => $this->stats(),
            'proxies' => $this->proxies(),
            'protocols' => Protocol::cases(),
            'checks' => Check::cases(),
            'sortOptions' => SortOption::cases(),
            'countries' => Cache::rememberForever('countries', fn () => (new ISO3166)->all()),
        ]);
    }

    /**
     * @return array<int, array{label: string, value: string, accent?: bool}>
     */
    private function stats(): array
    {
        return [
            ['label' => __('Tracked'), 'value' => '0'],
            ['label' => __('Passing now'), 'value' => '0', 'accent' => true],
            ['label' => __('Avg latency'), 'value' => '0 ms'],
            ['label' => __('Active sources'), 'value' => '0'],
        ];
    }

    /**
     * @return array<int, array{address: string, protocol: string, country: string, google: bool, cloudflare: bool, latency: ?int, bars: int, checked: string}>
     */
    private function proxies(): array
    {
        return [
            ['address' => 'X', 'protocol' => 'X', 'country' => 'X', 'google' => false, 'cloudflare' => false, 'latency' => null, 'bars' => 0, 'checked' => 'X'],
        ];
    }
}
