<?php

use App\Models\Proxy;

beforeEach(function () {
    Proxy::factory()->createMany([
        [
            'address' => '1.1.1.1', 'port' => 8080, 'protocol' => 'https', 'anonymity' => 'elite',
            'country' => 'DE', 'google_pass' => true, 'cloudflare_pass' => false,
            'latency_ms' => 50, 'is_active' => true, 'last_checked_at' => now()->subMinutes(5),
        ],
        [
            'address' => '2.2.2.2', 'port' => 3128, 'protocol' => 'http', 'anonymity' => 'anonymous',
            'country' => 'US', 'google_pass' => true, 'cloudflare_pass' => true,
            'latency_ms' => 120, 'is_active' => true, 'last_checked_at' => now()->subMinutes(3),
        ],
        [
            'address' => '3.3.3.3', 'port' => 1080, 'protocol' => 'socks5', 'anonymity' => 'transparent',
            'country' => 'FR', 'google_pass' => false, 'cloudflare_pass' => false,
            'latency_ms' => 500, 'is_active' => false, 'last_checked_at' => now()->subMinutes(10),
        ],
    ]);
});

// ─── Headers ────────────────────────────────────────────────────────────────

test('csv export returns correct headers', function () {
    $this->get(route('export.proxies', 'csv'))
        ->assertHeader('Content-Type', 'text/csv; charset=utf-8')
        ->assertHeader('Content-Disposition');
});

test('txt export returns correct headers', function () {
    $this->get(route('export.proxies', 'txt'))
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
        ->assertHeader('Content-Disposition');
});

test('json export returns correct headers', function () {
    $this->get(route('export.proxies', 'json'))
        ->assertHeader('Content-Type', 'application/json')
        ->assertHeader('Content-Disposition');
});

// ─── Filter: Protocol ──────────────────────────────────────────────────────

test('csv export respects protocol filter', function () {
    $response = $this->get(route('export.proxies', ['format' => 'csv', 'protocols' => ['https']]));

    $content = $response->streamedContent();
    expect($content)->toContain('1.1.1.1:8080')
        ->not->toContain('2.2.2.2:3128')
        ->not->toContain('3.3.3.3:1080');
});

// ─── Filter: Country ────────────────────────────────────────────────────────

test('csv export respects country filter', function () {
    $response = $this->get(route('export.proxies', ['format' => 'csv', 'country' => 'US']));

    $content = $response->streamedContent();
    expect($content)->toContain('2.2.2.2:3128')
        ->not->toContain('1.1.1.1:8080');
});

// ─── Filter: Checks ─────────────────────────────────────────────────────────

test('csv export respects check filter', function () {
    $response = $this->get(route('export.proxies', ['format' => 'csv', 'checks' => ['google', 'cloudflare']]));

    $content = $response->streamedContent();
    expect($content)->toContain('2.2.2.2:3128')
        ->not->toContain('1.1.1.1:8080') // google only, no cloudflare
        ->not->toContain('3.3.3.3:1080'); // neither
});

// ─── Filter: Active-only ────────────────────────────────────────────────────

test('csv export respects active-only filter', function () {
    $response = $this->get(route('export.proxies', ['format' => 'csv', 'active_only' => '1']));

    $content = $response->streamedContent();
    expect($content)->toContain('1.1.1.1:8080')
        ->toContain('2.2.2.2:3128')
        ->not->toContain('3.3.3.3:1080');
});

// ─── Filter: Search ─────────────────────────────────────────────────────────

test('csv export respects search filter', function () {
    $response = $this->get(route('export.proxies', ['format' => 'csv', 'search' => '1.1.1']));

    $content = $response->streamedContent();
    expect($content)->toContain('1.1.1.1:8080')
        ->not->toContain('2.2.2.2:3128');
});

// ─── Format: TXT ────────────────────────────────────────────────────────────

test('txt export uses ip:port format by default', function () {
    $response = $this->get(route('export.proxies', 'txt'));

    $content = $response->streamedContent();
    expect($content)->toContain('1.1.1.1:8080')
        ->toContain('2.2.2.2:3128');
});

test('txt export uses protocol://ip:port when data_format is set', function () {
    $response = $this->get(route('export.proxies', ['format' => 'txt', 'data_format' => 'protocol_ip_port']));

    $content = $response->streamedContent();
    expect($content)->toContain('https://1.1.1.1:8080')
        ->toContain('http://2.2.2.2:3128');
});

// ─── Format: JSON ───────────────────────────────────────────────────────────

test('json export contains address and protocol fields', function () {
    $response = $this->get(route('export.proxies', 'json'));

    $content = $response->streamedContent();
    $data = json_decode($content, true);

    expect($data)->toHaveCount(3)
        ->and($data[0])->toHaveKeys(['address', 'protocol']);
});

// ─── Empty results ──────────────────────────────────────────────────────────

test('csv export produces valid empty file with no results', function () {
    Proxy::query()->delete();

    $response = $this->get(route('export.proxies', 'csv'));

    $content = $response->streamedContent();
    expect($content)->toBe('');
});

test('txt export produces empty file with no results', function () {
    Proxy::query()->delete();

    $response = $this->get(route('export.proxies', 'txt'));

    $content = $response->streamedContent();
    expect($content)->toBe('');
});

test('json export produces empty array with no results', function () {
    Proxy::query()->delete();

    $response = $this->get(route('export.proxies', 'json'));

    $content = $response->streamedContent();
    expect($content)->toBe('[]');
});
