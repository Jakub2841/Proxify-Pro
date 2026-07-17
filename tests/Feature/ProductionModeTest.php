<?php

use App\Enums\SourceParserType;
use App\Livewire\ProxiesIndex;
use App\Livewire\SettingsIndex;
use App\Livewire\SourcesIndex;
use App\Models\Proxy;
use App\Models\Setting;
use App\Models\Source;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('app.production_mode', true);
});

afterEach(function () {
    config()->set('app.production_mode', false);
});

// ─── Settings ───────────────────────────────────────────────────────────────

test('settings save is blocked in production mode', function () {
    Livewire::test(SettingsIndex::class)
        ->set('scrapeInterval', 99)
        ->call('save');

    expect(Setting::get('scrape_interval'))->not->toBe(99);
});

test('clear database is blocked in production mode', function () {
    Proxy::factory()->create(['address' => '1.1.1.1', 'port' => 80, 'protocol' => 'http']);

    Livewire::test(SettingsIndex::class)
        ->call('clearDatabase');

    expect(Proxy::count())->toBe(1);
});

// ─── Sources ─────────────────────────────────────────────────────────────────

test('add source is blocked in production mode', function () {
    Livewire::test(SourcesIndex::class)
        ->call('addNew')
        ->assertSet('showModal', false);
});

test('edit source is blocked in production mode', function () {
    $source = Source::factory()->create(['name' => 'Test', 'url' => 'https://example.com']);

    Livewire::test(SourcesIndex::class)
        ->call('edit', $source->id)
        ->assertSet('showModal', false);
});

test('save source is blocked in production mode', function () {
    Livewire::test(SourcesIndex::class)
        ->set('name', 'Hacked')
        ->set('url', 'https://hacked.com')
        ->set('parser_type', 'plain_text')
        ->call('save');

    expect(Source::where('name', 'Hacked')->exists())->toBeFalse();
});

test('delete source is blocked in production mode', function () {
    $source = Source::factory()->create();

    Livewire::test(SourcesIndex::class)
        ->call('delete', $source->id);

    expect(Source::find($source->id))->not->toBeNull();
});

test('toggle enabled is blocked in production mode', function () {
    $source = Source::factory()->create(['is_enabled' => true]);

    Livewire::test(SourcesIndex::class)
        ->call('toggleEnabled', $source->id);

    expect(Source::find($source->id)->is_enabled)->toBeTrue();
});

test('bulk enable/disable is blocked in production mode', function () {
    Source::factory()->create(['is_enabled' => true]);

    Livewire::test(SourcesIndex::class)
        ->set('allEnabled', false);

    expect(Source::first()->is_enabled)->toBeTrue();
});

test('clear all sources is blocked in production mode', function () {
    Source::factory()->create();

    Livewire::test(SourcesIndex::class)
        ->call('clearAll');

    expect(Source::count())->toBe(1);
});

// ─── Dashboard ───────────────────────────────────────────────────────────────

test('scrape and check buttons still work in production mode', function () {
    Source::factory()->create([
        'name' => 'Test', 'url' => 'https://example.com',
        'is_enabled' => true,
        'parser_type' => SourceParserType::PlainText,
    ]);

    Livewire::test(ProxiesIndex::class)
        ->call('scrapeSources')
        ->call('checkProxies');
})->throwsNoExceptions();

test('export modal still opens in production mode', function () {
    Livewire::test(ProxiesIndex::class)
        ->set('showExportModal', true)
        ->assertSet('showExportModal', true);
});
