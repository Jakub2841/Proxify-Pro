<?php

use App\Enums\AnonymityLevel;
use App\Enums\Check;
use App\Enums\Protocol;
use App\Enums\SortOption;
use App\Enums\SourceParserType;
use App\Jobs\ScrapeSourceJob;
use App\Livewire\ProxiesIndex;
use App\Models\Proxy;
use App\Models\Source;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    // Create a known set of proxies for deterministic filter testing.
    Proxy::factory()->createMany([
        // Google-passing HTTPS proxy from Germany, low latency, active, elite
        [
            'address' => '1.1.1.1',
            'port' => 8080,
            'protocol' => 'https',
            'anonymity' => 'elite',
            'country' => 'DE',
            'google_pass' => true,
            'cloudflare_pass' => false,
            'latency_ms' => 50,
            'is_active' => true,
            'last_checked_at' => now()->subMinutes(5),
        ],
        // Google + Cloudflare passing HTTP proxy from US, medium latency, active, anonymous
        [
            'address' => '2.2.2.2',
            'port' => 3128,
            'protocol' => 'http',
            'anonymity' => 'anonymous',
            'country' => 'US',
            'google_pass' => true,
            'cloudflare_pass' => true,
            'latency_ms' => 120,
            'is_active' => true,
            'last_checked_at' => now()->subMinutes(3),
        ],
        // SOCKS5 proxy from France, high latency, inactive, transparent
        [
            'address' => '3.3.3.3',
            'port' => 1080,
            'protocol' => 'socks5',
            'anonymity' => 'transparent',
            'country' => 'FR',
            'google_pass' => false,
            'cloudflare_pass' => false,
            'latency_ms' => 500,
            'is_active' => false,
            'last_checked_at' => now()->subMinutes(10),
        ],
        // SOCKS4 proxy from Germany, passes Cloudflare only, active, anonymous
        [
            'address' => '4.4.4.4',
            'port' => 4145,
            'protocol' => 'socks4',
            'anonymity' => 'anonymous',
            'country' => 'DE',
            'google_pass' => false,
            'cloudflare_pass' => true,
            'latency_ms' => 80,
            'is_active' => true,
            'last_checked_at' => now()->subMinute(),
        ],
        // HTTPS proxy from Japan, passes Google, active, no latency, elite
        [
            'address' => '5.5.5.5',
            'port' => 443,
            'protocol' => 'https',
            'anonymity' => 'elite',
            'country' => 'JP',
            'google_pass' => true,
            'cloudflare_pass' => false,
            'latency_ms' => null,
            'is_active' => true,
            'last_checked_at' => now()->subMinutes(2),
        ],
    ]);
});

// ─── Protocol filter ────────────────────────────────────────────────────────

test('filters proxies by a single protocol', function () {
    Livewire::test(ProxiesIndex::class)
        ->call('toggleProtocol', 'https')
        ->assertViewHas('proxies', function ($proxies) {
            return $proxies->count() === 2
                && $proxies->every(fn ($p) => $p->protocol === Protocol::Https);
        });
});

test('filters proxies by multiple protocols', function () {
    Livewire::test(ProxiesIndex::class)
        ->call('toggleProtocol', 'https')
        ->call('toggleProtocol', 'socks5')
        ->assertViewHas('proxies', function ($proxies) {
            $values = $proxies->pluck('protocol.value');

            return $proxies->count() === 3
                && $values->contains('https')
                && $values->contains('socks5');
        });
});

test('toggling a protocol off removes it from the filter', function () {
    Livewire::test(ProxiesIndex::class)
        ->call('toggleProtocol', 'https')
        ->assertViewHas('proxies', fn ($p) => $p->count() === 2)
        ->call('toggleProtocol', 'https') // toggle back off
        ->assertViewHas('proxies', fn ($p) => $p->count() === 5);
});

// ─── Check filter ───────────────────────────────────────────────────────────

test('filters proxies by google pass check', function () {
    Livewire::test(ProxiesIndex::class)
        ->call('toggleCheck', Check::Google->value)
        ->assertViewHas('proxies', function ($proxies) {
            return $proxies->count() === 3
                && $proxies->every(fn ($p) => $p->google_pass === true);
        });
});

test('filters proxies by cloudflare pass check', function () {
    Livewire::test(ProxiesIndex::class)
        ->call('toggleCheck', Check::Cloudflare->value)
        ->assertViewHas('proxies', function ($proxies) {
            return $proxies->count() === 2
                && $proxies->every(fn ($p) => $p->cloudflare_pass === true);
        });
});

test('combining checks filters by both (AND logic)', function () {
    Livewire::test(ProxiesIndex::class)
        ->call('toggleCheck', Check::Google->value)
        ->call('toggleCheck', Check::Cloudflare->value)
        ->assertViewHas('proxies', function ($proxies) {
            return $proxies->count() === 1
                && $proxies->first()->address === '2.2.2.2';
        });
});

// ─── Country filter ─────────────────────────────────────────────────────────

test('filters proxies by country', function () {
    Livewire::test(ProxiesIndex::class)
        ->set('country', 'DE')
        ->assertViewHas('proxies', function ($proxies) {
            return $proxies->count() === 2
                && $proxies->every(fn ($p) => $p->country === 'DE');
        });
});

test('clearing country filter shows all proxies', function () {
    Livewire::test(ProxiesIndex::class)
        ->set('country', 'DE')
        ->assertViewHas('proxies', fn ($p) => $p->count() === 2)
        ->set('country', '')
        ->assertViewHas('proxies', fn ($p) => $p->count() === 5);
});

// ─── Active-only filter ─────────────────────────────────────────────────────

test('filters to active proxies only', function () {
    Livewire::test(ProxiesIndex::class)
        ->set('activeOnly', true)
        ->assertViewHas('proxies', function ($proxies) {
            return $proxies->count() === 4
                && $proxies->every(fn ($p) => $p->is_active === true);
        });
});

// ─── Search filter ──────────────────────────────────────────────────────────

test('filters by address search', function () {
    Livewire::test(ProxiesIndex::class)
        ->set('search', '1.1.1')
        ->assertViewHas('proxies', function ($proxies) {
            return $proxies->count() === 1
                && $proxies->first()->address === '1.1.1.1';
        });
});

test('search is case-insensitive partial match', function () {
    // Create a proxy with a distinct address to search for
    Proxy::factory()->create([
        'address' => '192.168.1.100',
        'port' => 9999,
        'protocol' => 'http',
        'country' => 'GB',
    ]);

    Livewire::test(ProxiesIndex::class)
        ->set('search', '192.168')
        ->assertViewHas('proxies', function ($proxies) {
            return $proxies->count() === 1
                && $proxies->first()->address === '192.168.1.100';
        });
});

// ─── Sort filter ────────────────────────────────────────────────────────────

test('sorts by latency ascending, excluding nulls', function () {
    Livewire::test(ProxiesIndex::class)
        ->set('sort', SortOption::LatencyAsc->value)
        ->assertViewHas('proxies', function ($proxies) {
            $latencies = $proxies->pluck('latency_ms')->toArray();

            return $latencies === [50, 80, 120, 500];
        });
});

test('sorts by latency descending, excluding nulls', function () {
    Livewire::test(ProxiesIndex::class)
        ->set('sort', SortOption::LatencyDesc->value)
        ->assertViewHas('proxies', function ($proxies) {
            $latencies = $proxies->pluck('latency_ms')->toArray();

            return $latencies === [500, 120, 80, 50];
        });
});

test('default sort is last checked descending', function () {
    Livewire::test(ProxiesIndex::class)
        ->assertViewHas('proxies', function ($proxies) {
            return $proxies->first()->address === '4.4.4.4'; // checked 1 min ago
        });
});

// ─── Pagination reset ───────────────────────────────────────────────────────

test('resets to page 1 when a filter changes', function () {
    Livewire::test(ProxiesIndex::class)
        ->set('country', 'DE')
        ->assertViewHas('proxies', fn ($p) => $p->currentPage() === 1);
});

// ─── Combined filters ───────────────────────────────────────────────────────

test('combines protocol + check + country filters', function () {
    Livewire::test(ProxiesIndex::class)
        ->call('toggleProtocol', 'https')
        ->call('toggleCheck', Check::Google->value)
        ->set('country', 'DE')
        ->assertViewHas('proxies', function ($proxies) {
            return $proxies->count() === 1
                && $proxies->first()->address === '1.1.1.1';
        });
});

test('combines active-only + search filters', function () {
    Livewire::test(ProxiesIndex::class)
        ->set('activeOnly', true)
        ->set('search', '1.1')
        ->assertViewHas('proxies', function ($proxies) {
            return $proxies->count() === 1
                && $proxies->first()->is_active === true
                && str_contains($proxies->first()->address, '1.1');
        });
});

// ─── Stats reflect filters ──────────────────────────────────────────────────

test('stats show filtered counts when filters are active', function () {
    Livewire::test(ProxiesIndex::class)
        ->call('toggleProtocol', 'https')
        ->set('activeOnly', true)
        ->assertViewHas('stats', function ($stats) {
            // First stat should be "Filtered" not "Tracked"
            return $stats[0]['label'] === 'Filtered'
                && $stats[0]['value'] === '2'; // 1.1.1.1 + 5.5.5.5
        });
});

test('stats show global counts when no filters are active', function () {
    Livewire::test(ProxiesIndex::class)
        ->assertViewHas('stats', function ($stats) {
            return $stats[0]['label'] === 'Tracked'
                && $stats[0]['value'] === '5';
        });
});

// ─── Anonymity filter ───────────────────────────────────────────────────────

test('filters proxies by anonymity level', function () {
    Livewire::test(ProxiesIndex::class)
        ->call('toggleAnonymity', AnonymityLevel::Elite->value)
        ->assertViewHas('proxies', function ($proxies) {
            return $proxies->count() === 2
                && $proxies->every(fn ($p) => $p->anonymity === AnonymityLevel::Elite);
        });
});

test('filters proxies by multiple anonymity levels', function () {
    Livewire::test(ProxiesIndex::class)
        ->call('toggleAnonymity', AnonymityLevel::Elite->value)
        ->call('toggleAnonymity', AnonymityLevel::Anonymous->value)
        ->assertViewHas('proxies', function ($proxies) {
            return $proxies->count() === 4;
        });
});

test('toggling an anonymity level off removes the filter', function () {
    Livewire::test(ProxiesIndex::class)
        ->call('toggleAnonymity', AnonymityLevel::Elite->value)
        ->assertViewHas('proxies', fn ($p) => $p->count() === 2)
        ->call('toggleAnonymity', AnonymityLevel::Elite->value)
        ->assertViewHas('proxies', fn ($p) => $p->count() === 5);
});

// ─── Scrape ─────────────────────────────────────────────────────────────────

test('scrape button queues enabled sources and shows toast', function () {
    Queue::fake();

    Source::factory()->create([
        'name' => 'Test Source',
        'url' => 'https://example.com/proxies.txt',
        'is_enabled' => true,
        'parser_type' => SourceParserType::PlainText,
        'default_protocol' => Protocol::Http,
    ]);

    Livewire::test(ProxiesIndex::class)
        ->call('scrapeSources');

    Queue::assertPushed(ScrapeSourceJob::class, 1);
});

test('scrape toast warns when no enabled sources exist', function () {
    Queue::fake();

    Source::factory()->create([
        'name' => 'Disabled Source',
        'url' => 'https://example.com/proxies.txt',
        'is_enabled' => false,
        'parser_type' => SourceParserType::PlainText,
        'default_protocol' => Protocol::Http,
    ]);

    Livewire::test(ProxiesIndex::class)
        ->call('scrapeSources');

    Queue::assertNothingPushed();
});
