<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Setting::put('scrape_enabled', true);
    Setting::put('scrape_interval', 30);
    Setting::put('check_enabled', true);
    Setting::put('check_interval', 15);
    Cache::forget('last_auto_scrape');
    Cache::forget('last_auto_check');
});

test('runs scrape and check on first invocation', function () {
    Artisan::call('auto:schedule');

    expect(Cache::get('last_auto_scrape'))->not->toBeNull();
    expect(Cache::get('last_auto_check'))->not->toBeNull();
});

test('skips scrape when disabled', function () {
    Setting::put('scrape_enabled', false);

    Artisan::call('auto:schedule');

    expect(Cache::get('last_auto_scrape'))->toBeNull();
    expect(Cache::get('last_auto_check'))->not->toBeNull();
});

test('skips check when disabled', function () {
    Setting::put('check_enabled', false);

    Artisan::call('auto:schedule');

    expect(Cache::get('last_auto_scrape'))->not->toBeNull();
    expect(Cache::get('last_auto_check'))->toBeNull();
});

test('does not re-run before interval elapses', function () {
    Artisan::call('auto:schedule');

    $firstScrape = Cache::get('last_auto_scrape');

    Artisan::call('auto:schedule');

    // Cache key unchanged — command skipped because interval hasn't passed.
    expect(Cache::get('last_auto_scrape'))->toBe($firstScrape);
});

test('runs again after interval elapses', function () {
    Cache::put('last_auto_scrape', now()->subMinutes(31)->toIso8601String());
    Cache::put('last_auto_check', now()->subMinutes(16)->toIso8601String());

    Artisan::call('auto:schedule');

    $scrapeTime = Cache::get('last_auto_scrape');
    $checkTime = Cache::get('last_auto_check');

    expect(now()->diffInSeconds($scrapeTime))->toBeLessThan(5);
    expect(now()->diffInSeconds($checkTime))->toBeLessThan(5);
});
