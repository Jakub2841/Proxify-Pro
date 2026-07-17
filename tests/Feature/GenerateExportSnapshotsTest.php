<?php

use App\Jobs\GenerateExportSnapshots;
use App\Models\Proxy;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    Proxy::factory()->createMany([
        [
            'address' => '1.1.1.1', 'port' => 8080, 'protocol' => 'https', 'is_active' => true,
            'country' => 'DE', 'google_pass' => true, 'cloudflare_pass' => false,
            'latency_ms' => 50, 'last_checked_at' => now()->subMinutes(5),
        ],
        [
            'address' => '2.2.2.2', 'port' => 3128, 'protocol' => 'http', 'is_active' => true,
            'country' => 'US', 'google_pass' => true, 'cloudflare_pass' => true,
            'latency_ms' => 120, 'last_checked_at' => now()->subMinutes(3),
        ],
        [
            'address' => '3.3.3.3', 'port' => 1080, 'protocol' => 'socks5', 'is_active' => false,
            'country' => 'FR', 'google_pass' => false, 'cloudflare_pass' => false,
            'latency_ms' => 500, 'last_checked_at' => now()->subMinutes(10),
        ],
        [
            'address' => '4.4.4.4', 'port' => 4145, 'protocol' => 'socks4', 'is_active' => true,
            'country' => 'DE', 'google_pass' => false, 'cloudflare_pass' => true,
            'latency_ms' => 80, 'last_checked_at' => now()->subMinute(),
        ],
    ]);
});

test('generates all expected files', function () {
    (new GenerateExportSnapshots)->handle();

    $disk = Storage::disk('public');

    expect($disk->exists('proxies/all.txt'))->toBeTrue();
    expect($disk->exists('proxies/all.csv'))->toBeTrue();
    expect($disk->exists('proxies/all.json'))->toBeTrue();
    expect($disk->exists('proxies/https.txt'))->toBeTrue();
    expect($disk->exists('proxies/http.txt'))->toBeTrue();
    expect($disk->exists('proxies/socks4.txt'))->toBeTrue();
    expect($disk->exists('proxies/socks5.txt'))->toBeTrue();
});

test('all.txt contains only active proxies', function () {
    (new GenerateExportSnapshots)->handle();

    $content = Storage::disk('public')->get('proxies/all.txt');

    expect($content)->toContain('https://1.1.1.1:8080')
        ->toContain('http://2.2.2.2:3128')
        ->toContain('socks4://4.4.4.4:4145')
        ->not->toContain('3.3.3.3'); // inactive
});

test('protocol-specific files are filtered correctly', function () {
    (new GenerateExportSnapshots)->handle();

    $https = Storage::disk('public')->get('proxies/https.txt');
    expect($https)->toContain('1.1.1.1:8080')
        ->not->toContain('2.2.2.2');

    $socks5 = Storage::disk('public')->get('proxies/socks5.txt');
    expect($socks5)->toBe(''); // socks5 proxy is inactive, empty file
});

test('all.csv contains only addresses with no header', function () {
    (new GenerateExportSnapshots)->handle();

    $content = Storage::disk('public')->get('proxies/all.csv');

    expect($content)->toContain('1.1.1.1:8080')
        ->toContain('2.2.2.2:3128')
        ->toContain('4.4.4.4:4145')
        ->not->toContain('Address')  // no header
        ->not->toContain('pass')     // no extra columns
        ->not->toContain('fail');
});

test('all.json contains only address and protocol', function () {
    (new GenerateExportSnapshots)->handle();

    $json = Storage::disk('public')->get('proxies/all.json');
    $data = json_decode($json, true);

    expect($data)->toHaveCount(3)
        ->and($data[0])->toHaveKeys(['address', 'protocol'])
        ->and($data[0])->not->toHaveKeys(['country', 'google_pass', 'latency_ms']);
});

test('running job twice replaces files without leftover tmp files', function () {
    (new GenerateExportSnapshots)->handle();
    (new GenerateExportSnapshots)->handle();

    $files = Storage::disk('public')->allFiles('proxies');

    // No .tmp files should remain
    foreach ($files as $file) {
        expect($file)->not->toEndWith('.tmp');
    }

    // Files should still exist and be valid
    expect(Storage::disk('public')->exists('proxies/all.txt'))->toBeTrue();
});
