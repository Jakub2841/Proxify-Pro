<?php

namespace App\Livewire;

use App\Enums\AnonymityLevel;
use App\Enums\Check;
use App\Enums\Protocol;
use App\Enums\SortOption;
use App\Models\Proxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use League\ISO3166\ISO3166;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ProxiesIndex extends Component
{
    use WithPagination;

    /** @var string[] */
    public array $activeProtocols = [];

    /** @var string[] */
    public array $activeChecks = [];

    /** @var string[] */
    public array $activeAnonymity = [];

    public string $country = '';

    public string $sort = 'last_checked_desc';

    public string $search = '';

    public bool $activeOnly = false;

    public function toggleProtocol(string $value): void
    {
        $this->toggleInArray($value, $this->activeProtocols);
    }

    public function toggleCheck(string $value): void
    {
        $this->toggleInArray($value, $this->activeChecks);
    }

    public function toggleAnonymity(string $value): void
    {
        $this->toggleInArray($value, $this->activeAnonymity);
    }

    private function toggleInArray(string $value, array &$target): void
    {
        if (in_array($value, $target, true)) {
            $target = array_values(array_diff($target, [$value]));
        } else {
            $target[] = $value;
        }

        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCountry(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function applySort(string $value): void
    {
        $this->sort = $value;
        $this->resetPage();
    }

    public function updatedActiveOnly(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = $this->buildQuery(Proxy::query());

        match ($this->sort) {
            SortOption::LatencyAsc->value => $query->orderBy('latency_ms', 'asc'),
            SortOption::LatencyDesc->value => $query->orderBy('latency_ms', 'desc'),
            default => $query->latest('last_checked_at'),
        };

        return view('components.⚡proxies-index', [
            'stats' => $this->stats($query),
            'proxies' => $query->paginate(20),
            'protocols' => Protocol::cases(),
            'checks' => Check::cases(),
            'anonymityLevels' => AnonymityLevel::cases(),
            'sortOptions' => SortOption::cases(),
            'countries' => Cache::rememberForever('countries', fn () => (new ISO3166)->all()),
        ]);
    }

    /**
     * Apply all active filters to the query.
     */
    private function buildQuery(Builder $query): Builder
    {
        if ($this->activeProtocols !== []) {
            $query->whereIn('protocol', $this->activeProtocols);
        }

        if ($this->activeChecks !== []) {
            foreach ($this->activeChecks as $check) {
                match ($check) {
                    Check::Google->value => $query->where('google_pass', true),
                    Check::Cloudflare->value => $query->where('cloudflare_pass', true),
                    default => null,
                };
            }
        }

        if ($this->activeAnonymity !== []) {
            $query->whereIn('anonymity', $this->activeAnonymity);
        }

        if ($this->country !== '') {
            $query->where('country', $this->country);
        }

        if ($this->activeOnly) {
            $query->where('is_active', true);
        }

        if ($this->search !== '') {
            $query->where('address', 'like', '%'.$this->search.'%');
        }

        return $query;
    }

    /**
     * @return array<int, array{label: string, value: string, accent?: bool}>
     */
    private function stats(Builder $query): array
    {
        $isFiltered = $this->activeProtocols !== [] || $this->activeChecks !== [] || $this->activeAnonymity !== [] || $this->country !== '' || $this->activeOnly || $this->search !== '';

        if ($isFiltered) {
            return [
                ['label' => __('Filtered'), 'value' => number_format((clone $query)->toBase()->count())],
                ['label' => __('Passing'), 'value' => number_format((clone $query)->where('google_pass', true)->count()), 'accent' => true],
                ['label' => __('Avg latency'), 'value' => round((clone $query)->whereNotNull('latency_ms')->avg('latency_ms') ?? 0).' ms'],
                ['label' => __('Active'), 'value' => number_format((clone $query)->where('is_active', true)->count())],
            ];
        }

        return Cache::get('proxy-stats', fn () => [
            ['label' => __('Tracked'), 'value' => number_format(Proxy::count())],
            ['label' => __('Passing now'), 'value' => number_format(Proxy::where('is_active', true)->where('google_pass', true)->count()), 'accent' => true],
            ['label' => __('Avg latency'), 'value' => round(Proxy::whereNotNull('latency_ms')->avg('latency_ms') ?? 0).' ms'],
            ['label' => __('Active sources'), 'value' => number_format(Proxy::where('is_active', true)->count())],
        ]);
    }
}
