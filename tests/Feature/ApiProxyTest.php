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

// ─── JSON endpoint ──────────────────────────────────────────────────────────

test('GET /api/proxies returns paginated JSON', function () {
    $this->getJson('/api/proxies')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['address', 'port', 'protocol', 'anonymity', 'country', 'google_pass', 'cloudflare_pass', 'latency_ms', 'last_checked_at']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

test('GET /api/proxies respects protocol filter', function () {
    $response = $this->getJson('/api/proxies?protocols[]=https');

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(1)
        ->and(collect($data)->every(fn ($p) => $p['protocol'] === 'https'))->toBeTrue();
});

test('GET /api/proxies respects country filter', function () {
    $response = $this->getJson('/api/proxies?country=US');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

test('GET /api/proxies respects active_only filter', function () {
    $response = $this->getJson('/api/proxies?active_only=1');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

test('GET /api/proxies respects per_page param', function () {
    $response = $this->getJson('/api/proxies?per_page=1');

    $response->assertOk();
    expect($response->json('meta.per_page'))->toBe(1)
        ->and($response->json('data'))->toHaveCount(1);
});

// ─── TXT endpoint ───────────────────────────────────────────────────────────

test('GET /api/proxies/txt returns plain text list', function () {
    $response = $this->get('/api/proxies/txt');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    expect($response->streamedContent())->toContain('https://1.1.1.1:8080')
        ->toContain('http://2.2.2.2:3128')
        ->not->toContain('3.3.3.3'); // inactive
});

test('GET /api/proxies/txt respects protocol filter', function () {
    $response = $this->get('/api/proxies/txt?protocols[]=https');

    expect($response->streamedContent())->toContain('1.1.1.1:8080')
        ->not->toContain('2.2.2.2');
});

test('GET /api/proxies/txt returns empty when no matches', function () {
    Proxy::query()->delete();

    $response = $this->get('/api/proxies/txt');

    expect($response->streamedContent())->toBe('');
});
