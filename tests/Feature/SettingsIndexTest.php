<?php

use App\Livewire\SettingsIndex;
use App\Models\Setting;
use Livewire\Livewire;

test('renders with default values when no settings exist', function () {
    Livewire::test(SettingsIndex::class)
        ->assertSet('scrapeInterval', 30)
        ->assertSet('checkInterval', 15)
        ->assertSet('maxLatency', 2000)
        ->assertSet('apiAccess', true)
        ->assertSet('saveInactive', false);
});

test('loads existing settings from database', function () {
    Setting::put('scrape_interval', 60);
    Setting::put('check_interval', 10);
    Setting::put('max_latency', 1000);
    Setting::put('api_access', false);
    Setting::put('save_inactive', true);

    Livewire::test(SettingsIndex::class)
        ->assertSet('scrapeInterval', 60)
        ->assertSet('checkInterval', 10)
        ->assertSet('maxLatency', 1000)
        ->assertSet('apiAccess', false)
        ->assertSet('saveInactive', true);
});

test('save persists settings to database', function () {
    Livewire::test(SettingsIndex::class)
        ->set('scrapeInterval', 45)
        ->set('apiAccess', false)
        ->call('save');

    expect(Setting::get('scrape_interval'))->toBe(45);
    expect(Setting::get('api_access'))->toBe(false);
});

test('save preserves boolean false correctly', function () {
    Livewire::test(SettingsIndex::class)
        ->set('saveInactive', true)
        ->call('save');

    expect(Setting::get('save_inactive'))->toBeTrue();

    Livewire::test(SettingsIndex::class)
        ->set('saveInactive', false)
        ->call('save');

    expect(Setting::get('save_inactive'))->toBeFalse();
});
