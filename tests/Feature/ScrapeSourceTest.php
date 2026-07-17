<?php

use App\Actions\ScrapeSource;
use App\Enums\AnonymityLevel;
use App\Enums\Protocol;
use App\Enums\SourceParserType;
use App\Models\Proxy;
use App\Models\Source;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

test('scrapes a plain text source and inserts new proxies', function () {
    Http::fake([
        'https://example.com/proxies.txt' => Http::response(
            "192.168.1.1:8080\n10.0.0.1:3128\n172.16.0.1:9999",
            200,
        ),
    ]);

    $source = Source::factory()->create([
        'url' => 'https://example.com/proxies.txt',
        'parser_type' => SourceParserType::PlainText,
        'default_protocol' => Protocol::Http,
    ]);

    $inserted = app(ScrapeSource::class)($source);

    expect($inserted)->toBe(3);
    expect(Proxy::count())->toBe(3);
    expect($source->fresh()->proxy_count)->toBe(3);
    expect($source->fresh()->last_scraped_at)->not->toBeNull();
});

test('skips duplicates on re-scrape', function () {
    Proxy::create([
        'address' => '192.168.1.1',
        'port' => 8080,
        'protocol' => Protocol::Http,
        'is_active' => true,
    ]);

    Http::fake([
        'https://example.com/proxies.txt' => Http::response(
            "192.168.1.1:8080\n10.0.0.1:3128",
            200,
        ),
    ]);

    $source = Source::factory()->create([
        'url' => 'https://example.com/proxies.txt',
        'parser_type' => SourceParserType::PlainText,
        'default_protocol' => Protocol::Http,
    ]);

    $inserted = app(ScrapeSource::class)($source);

    expect($inserted)->toBe(1); // only the new one
    expect(Proxy::count())->toBe(2);
});

test('propagates HTTP exceptions to the caller', function () {
    Http::fake([
        'https://unreachable.example.com' => fn () => throw new ConnectionException('timeout'),
    ]);

    $source = Source::factory()->create([
        'url' => 'https://unreachable.example.com',
        'parser_type' => SourceParserType::PlainText,
        'default_protocol' => Protocol::Http,
    ]);

    $thrown = false;

    try {
        app(ScrapeSource::class)($source);
    } catch (ConnectionException) {
        $thrown = true;
    }

    expect($thrown)->toBeTrue();
});

test('scrapes a json api source and inserts proxies with anonymity', function () {
    Http::fake([
        'https://api.example.com/proxies' => Http::response(json_encode([
            'proxies' => [
                ['ip' => '192.168.1.1', 'port' => 8080, 'protocol' => 'socks5', 'anonymity' => 'elite'],
                ['ip' => '10.0.0.1', 'port' => 3128, 'protocol' => 'http', 'anonymity' => 'transparent'],
                ['ip' => '172.16.0.1', 'port' => 9999], // no protocol, uses default
            ],
        ]), 200),
    ]);

    $source = Source::factory()->create([
        'url' => 'https://api.example.com/proxies',
        'parser_type' => SourceParserType::JsonApi,
        'default_protocol' => Protocol::Http,
    ]);

    $inserted = app(ScrapeSource::class)($source);

    expect($inserted)->toBe(3);
    expect(Proxy::count())->toBe(3);

    $first = Proxy::where('address', '192.168.1.1')->first();
    expect($first->anonymity)->toBe(AnonymityLevel::Elite);
    expect($first->protocol)->toBe(Protocol::Socks5);

    $third = Proxy::where('address', '172.16.0.1')->first();
    expect($third->protocol)->toBe(Protocol::Http); // default
});
