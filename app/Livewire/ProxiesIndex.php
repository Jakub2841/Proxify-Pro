<?php

namespace App\Livewire;

use App\Enums\AnonymityLevel;
use App\Enums\Check;
use App\Enums\Protocol;
use App\Enums\SortOption;
use App\Jobs\ScrapeSourceJob;
use App\Models\Proxy;
use App\Models\Source;
use Flux\Flux;
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

    public bool $showExportModal = false;

    public string $exportFormat = 'csv';

    public string $exportDataFormat = 'ip_port';

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

    public function applyCountry(string $value): void
    {
        $this->country = $value;
        $this->resetPage();
    }

    public function updatedActiveOnly(): void
    {
        $this->resetPage();
    }

    public function scrapeSources(): void
    {
        $sources = Source::where('is_enabled', true)->get();

        if ($sources->isEmpty()) {
            Flux::toast(__('No enabled sources to scrape.'), variant: 'warning');

            return;
        }

        foreach ($sources as $source) {
            ScrapeSourceJob::dispatch($source);
        }

        Flux::toast(
            __(':count sources dispatched for scraping.', ['count' => $sources->count()]),
            variant: 'success',
        );
    }

    public function exportUrl(): string
    {
        return route('export.proxies', [
            'format' => $this->exportFormat,
            'data_format' => $this->exportDataFormat,
            'protocols' => $this->activeProtocols,
            'anonymity' => $this->activeAnonymity,
            'checks' => $this->activeChecks,
            'country' => $this->country ?: null,
            'active_only' => $this->activeOnly ? '1' : null,
            'search' => $this->search ?: null,
        ]);
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
     * Apply all active filters via the shared model scope.
     */
    private function buildQuery(Builder $query): Builder
    {
        return $query->filtered([
            'protocols' => $this->activeProtocols,
            'anonymity' => $this->activeAnonymity,
            'checks' => $this->activeChecks,
            'country' => $this->country,
            'active_only' => $this->activeOnly,
            'search' => $this->search,
        ]);
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
