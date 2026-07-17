<?php

use App\Jobs\CheckProxies;
use App\Jobs\CheckProxy;
use App\Models\Proxy;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function fakeCheckTargets(array $overrides = []): void
{
    Http::fake(array_merge([
        'www.google.com' => Http::response('<html>Search</html>', 200),
        'www.cloudflare.com' => Http::response('<html>Cloudflare</html>', 200),
        'ip-api.com/*' => Http::response(['countryCode' => 'DE'], 200),
    ], $overrides));
}

test('marks proxy as passed on both targets and sets country', function () {
    fakeCheckTargets();

    $proxy = Proxy::factory()->create([
        'address' => '192.168.1.1', 'port' => 8080, 'protocol' => 'http',
        'google_pass' => false, 'cloudflare_pass' => false,
        'last_checked_at' => null, 'latency_ms' => null, 'country' => null,
    ]);

    (new CheckProxy($proxy))->handle();

    $proxy->refresh();

    expect($proxy->google_pass)->toBeTrue();
    expect($proxy->cloudflare_pass)->toBeTrue();
    expect($proxy->country)->toBe('DE');
    expect($proxy->last_checked_at)->not->toBeNull();
    expect($proxy->latency_ms)->toBeGreaterThan(0);
});

test('marks google failed but cloudflare passed independently', function () {
    fakeCheckTargets([
        'www.google.com' => fn () => throw new ConnectionException('timeout'),
    ]);

    $proxy = Proxy::factory()->create([
        'address' => '10.0.0.1', 'port' => 3128, 'protocol' => 'http',
        'google_pass' => true, 'cloudflare_pass' => false,
        'last_checked_at' => null,
    ]);

    (new CheckProxy($proxy))->handle();

    $proxy->refresh();

    expect($proxy->google_pass)->toBeFalse();
    expect($proxy->cloudflare_pass)->toBeTrue();
});

test('marks both as failed when proxy is dead', function () {
    Http::fake([
        'www.google.com' => fn () => throw new ConnectionException('timeout'),
        'www.cloudflare.com' => fn () => throw new ConnectionException('timeout'),
        'ip-api.com/*' => Http::response(['countryCode' => 'US'], 200),
    ]);

    $proxy = Proxy::factory()->create([
        'address' => '172.16.0.1', 'port' => 9999, 'protocol' => 'http',
        'google_pass' => true, 'cloudflare_pass' => true,
        'last_checked_at' => null,
    ]);

    (new CheckProxy($proxy))->handle();

    $proxy->refresh();

    expect($proxy->google_pass)->toBeFalse();
    expect($proxy->cloudflare_pass)->toBeFalse();
});

test('marks google failed on captcha response', function () {
    fakeCheckTargets([
        'www.google.com' => Http::response('<html><body>recaptcha challenge</body></html>', 200),
    ]);

    $proxy = Proxy::factory()->create([
        'address' => '192.168.1.1', 'port' => 8080, 'protocol' => 'http',
        'google_pass' => true, 'last_checked_at' => null,
    ]);

    (new CheckProxy($proxy))->handle();

    $proxy->refresh();

    expect($proxy->google_pass)->toBeFalse();
    expect($proxy->cloudflare_pass)->toBeTrue();
});

test('dispatches individual CheckProxy jobs for stale proxies only', function () {
    Queue::fake();

    Proxy::factory()->create([
        'address' => '1.1.1.1', 'port' => 80, 'protocol' => 'http',
        'last_checked_at' => now()->subMinutes(60),
    ]);

    Proxy::factory()->create([
        'address' => '2.2.2.2', 'port' => 80, 'protocol' => 'http',
        'last_checked_at' => now()->subMinutes(5),
    ]);

    (new CheckProxies)->handle();

    Queue::assertPushed(CheckProxy::class, 1);
});
