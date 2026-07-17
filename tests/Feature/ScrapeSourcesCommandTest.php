<?php

use App\Enums\Protocol;
use App\Enums\SourceParserType;
use App\Models\Proxy;
use App\Models\Source;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

test('scrapes all enabled sources and inserts proxies', function () {
    Http::fake([
        'https://example.com/proxies.txt' => Http::response(
            "192.168.1.1:8080\n10.0.0.1:3128\n172.16.0.1:9999",
            200,
        ),
    ]);

    Source::factory()->create([
        'name' => 'Test Source',
        'url' => 'https://example.com/proxies.txt',
        'is_enabled' => true,
        'parser_type' => SourceParserType::PlainText,
        'default_protocol' => Protocol::Http,
    ]);

    $this->artisan('scrape:sources')->assertSuccessful();

    expect(Proxy::count())->toBe(3);
});

test('skips disabled sources', function () {
    Http::fake([
        'https://example.com/proxies.txt' => Http::response('192.168.1.1:8080', 200),
    ]);

    Source::factory()->create([
        'name' => 'Disabled Source',
        'url' => 'https://example.com/proxies.txt',
        'is_enabled' => false,
        'parser_type' => SourceParserType::PlainText,
        'default_protocol' => Protocol::Http,
    ]);

    $this->artisan('scrape:sources')->assertSuccessful();

    expect(Proxy::count())->toBe(0);
});

test('handles per-source failures and continues', function () {
    Http::fake([
        'https://good.example.com/list.txt' => Http::response("192.168.1.1:8080\n10.0.0.1:3128", 200),
        'https://bad.example.com/list.txt' => fn () => throw new ConnectionException('timeout'),
    ]);

    Source::factory()->create([
        'name' => 'Good Source',
        'url' => 'https://good.example.com/list.txt',
        'is_enabled' => true,
        'parser_type' => SourceParserType::PlainText,
        'default_protocol' => Protocol::Http,
    ]);

    Source::factory()->create([
        'name' => 'Bad Source',
        'url' => 'https://bad.example.com/list.txt',
        'is_enabled' => true,
        'parser_type' => SourceParserType::PlainText,
        'default_protocol' => Protocol::Http,
    ]);

    $this->artisan('scrape:sources')->assertSuccessful();

    // Good source added 2 proxies; bad source failed but didn't block the rest.
    expect(Proxy::count())->toBe(2);

    $badSource = Source::where('name', 'Bad Source')->first();
    expect($badSource->last_error)->not->toBeNull();
});

test('completes with message when no enabled sources exist', function () {
    Source::factory()->create([
        'name' => 'Disabled Only',
        'url' => 'https://example.com/proxies.txt',
        'is_enabled' => false,
        'parser_type' => SourceParserType::PlainText,
        'default_protocol' => Protocol::Http,
    ]);

    $this->artisan('scrape:sources')->assertSuccessful();
});
