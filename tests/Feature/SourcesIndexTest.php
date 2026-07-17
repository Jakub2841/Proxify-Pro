<?php

use App\Livewire\SourcesIndex;
use App\Models\Source;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Source::factory()->createMany([
        ['name' => 'Alpha Source', 'url' => 'https://alpha.example.com', 'is_enabled' => true, 'is_active' => true, 'proxy_count' => 50],
        ['name' => 'Beta Source', 'url' => 'https://beta.example.com', 'is_enabled' => true, 'is_active' => false, 'proxy_count' => 30],
        ['name' => 'Gamma Source', 'url' => 'https://gamma.example.com', 'is_enabled' => false, 'is_active' => true, 'proxy_count' => 0],
    ]);
});

// ─── Renders sources ────────────────────────────────────────────────────────

test('renders all sources in the table', function () {
    Livewire::test(SourcesIndex::class)
        ->assertViewHas('sources', fn ($sources) => $sources->count() === 3);
});

// ─── CRUD: Create ───────────────────────────────────────────────────────────

test('adds a new source', function () {
    Livewire::test(SourcesIndex::class)
        ->set('name', 'Delta Source')
        ->set('url', 'https://delta.example.com')
        ->set('parser_type', 'plain_text')
        ->call('save')
        ->assertViewHas('sources', fn ($sources) => $sources->count() === 4);

    expect(Source::where('name', 'Delta Source')->exists())->toBeTrue();
});

test('validates required fields when adding', function () {
    Livewire::test(SourcesIndex::class)
        ->set('name', '')
        ->set('url', '')
        ->call('save')
        ->assertHasErrors(['name', 'url']);
});

test('validates URL format when adding', function () {
    Livewire::test(SourcesIndex::class)
        ->set('name', 'Test')
        ->set('url', 'not-a-url')
        ->call('save')
        ->assertHasErrors(['url']);
});

// ─── CRUD: Edit ─────────────────────────────────────────────────────────────

test('edits an existing source', function () {
    $source = Source::first();

    Livewire::test(SourcesIndex::class)
        ->call('edit', $source->id)
        ->assertSet('editingId', $source->id)
        ->assertSet('name', $source->name)
        ->set('name', 'Updated Name')
        ->call('save');

    expect(Source::find($source->id)->name)->toBe('Updated Name');
});

// ─── CRUD: Delete ───────────────────────────────────────────────────────────

test('deletes a source', function () {
    $source = Source::first();

    Livewire::test(SourcesIndex::class)
        ->call('delete', $source->id)
        ->assertViewHas('sources', fn ($sources) => $sources->count() === 2);

    expect(Source::find($source->id))->toBeNull();
});

// ─── Toggle enabled ─────────────────────────────────────────────────────────

test('toggles a source enabled state', function () {
    $source = Source::where('is_enabled', true)->first();

    Livewire::test(SourcesIndex::class)
        ->call('toggleEnabled', $source->id);

    expect(Source::find($source->id)->is_enabled)->toBeFalse();

    Livewire::test(SourcesIndex::class)
        ->call('toggleEnabled', $source->id);

    expect(Source::find($source->id)->is_enabled)->toBeTrue();
});

// ─── Bulk enable/disable ────────────────────────────────────────────────────

test('bulk toggle enables all when all are disabled', function () {
    Source::query()->update(['is_enabled' => false]);

    Livewire::test(SourcesIndex::class)
        ->set('allEnabled', true);

    expect(Source::where('is_enabled', true)->count())->toBe(3);
});

test('bulk toggle disables all when any are enabled', function () {
    Livewire::test(SourcesIndex::class)
        ->set('allEnabled', false);

    expect(Source::where('is_enabled', false)->count())->toBe(3);
});

// ─── Clear all ──────────────────────────────────────────────────────────────

test('clears all sources', function () {
    Livewire::test(SourcesIndex::class)
        ->call('clearAll')
        ->assertViewHas('sources', fn ($sources) => $sources->count() === 0);

    expect(Source::count())->toBe(0);
});

// ─── Modal state ────────────────────────────────────────────────────────────

test('showModal is true after clicking add', function () {
    Livewire::test(SourcesIndex::class)
        ->call('addNew')
        ->assertSet('showModal', true);
});

test('cancel resets state and closes modal', function () {
    Livewire::test(SourcesIndex::class)
        ->set('showModal', true)
        ->set('name', 'Stale Name')
        ->set('url', 'https://stale.example.com')
        ->set('detectionError', 'Some error')
        ->call('cancel')
        ->assertSet('showModal', false)
        ->assertSet('name', '')
        ->assertSet('url', '')
        ->assertSet('detectionError', null);
});

test('modal resets after save', function () {
    Livewire::test(SourcesIndex::class)
        ->call('addNew')
        ->set('name', 'New Source')
        ->set('url', 'https://new.example.com')
        ->set('parser_type', 'plain_text')
        ->call('save')
        ->assertSet('showModal', false)
        ->assertSet('name', '')
        ->assertSet('url', '')
        ->assertSet('parser_type', null)
        ->assertSet('editingId', null);
});

// ─── GitHub URL rewriting ───────────────────────────────────────────────────

test('rewrites github blob url to raw equivalent', function () {
    Livewire::test(SourcesIndex::class)
        ->call('addNew')
        ->set('url', 'https://github.com/user/repo/blob/main/proxies.txt')
        ->assertSet('url', 'https://raw.githubusercontent.com/user/repo/main/proxies.txt');
});

test('does not rewrite non-github urls', function () {
    Livewire::test(SourcesIndex::class)
        ->call('addNew')
        ->set('url', 'https://example.com/proxies.txt')
        ->assertSet('url', 'https://example.com/proxies.txt');
});

test('does not rewrite raw github urls that are already correct', function () {
    Livewire::test(SourcesIndex::class)
        ->call('addNew')
        ->set('url', 'https://raw.githubusercontent.com/user/repo/main/proxies.txt')
        ->assertSet('url', 'https://raw.githubusercontent.com/user/repo/main/proxies.txt');
});

// ─── Detection ──────────────────────────────────────────────────────────────

test('successful detection populates parser_type and parser_config', function () {
    Http::fake([
        'https://example.com/proxies' => Http::response(
            '<!DOCTYPE html><html><body><table id="proxylist">'
            .'<thead><tr><th>IP</th><th>Port</th></tr></thead><tbody>'
            .'<tr><td>192.168.1.1</td><td>8080</td></tr>'
            .'<tr><td>10.0.0.1</td><td>3128</td></tr>'
            .'<tr><td>172.16.0.1</td><td>9999</td></tr>'
            .'<tr><td>8.8.8.8</td><td>443</td></tr>'
            .'</tbody></table></body></html>',
            200,
        ),
    ]);

    Livewire::test(SourcesIndex::class)
        ->set('url', 'https://example.com/proxies')
        ->call('detectSource')
        ->assertSet('parser_type', 'html_table')
        ->assertSet('parser_config.row_selector', 'table#proxylist tr')
        ->assertSet('parser_config.address_col', 0)
        ->assertSet('parser_config.port_col', 1)
        ->assertSet('isDetecting', false);
});

test('failed HTTP fetch during detection surfaces error and resets loading', function () {
    Http::fake([
        'https://unreachable.example.com' => fn () => throw new ConnectionException('timeout'),
    ]);

    Livewire::test(SourcesIndex::class)
        ->set('url', 'https://unreachable.example.com')
        ->call('detectSource')
        ->assertSet('detectionError', fn ($v) => $v !== null)
        ->assertSet('isDetecting', false);
});

test('json api detection allows save', function () {
    Http::fake([
        'https://api.example.com/proxies' => Http::response('{"proxies":[]}', 200),
    ]);

    Livewire::test(SourcesIndex::class)
        ->set('url', 'https://api.example.com/proxies')
        ->call('detectSource')
        ->assertSet('parser_type', 'json_api')
        ->set('name', 'Test JSON')
        ->call('save')
        ->assertHasNoErrors();

    expect(Source::where('name', 'Test JSON')->exists())->toBeTrue();
});

test('low-confidence html table detection leaves parser_config empty', function () {
    Http::fake([
        'https://example.com/proxies' => Http::response(
            '<!DOCTYPE html><html><body><table>'
            .'<thead><tr><th>A</th><th>B</th><th>C</th></tr></thead><tbody>'
            .'<tr><td>foo</td><td>bar</td><td>baz</td></tr>'
            .'<tr><td>qux</td><td>quux</td><td>corge</td></tr>'
            .'<tr><td>nope</td><td>nada</td><td>nothing</td></tr>'
            .'</tbody></table></body></html>',
            200,
        ),
    ]);

    Livewire::test(SourcesIndex::class)
        ->set('url', 'https://example.com/proxies')
        ->call('detectSource')
        ->assertSet('parser_type', 'html_table')
        ->assertSet('parser_config', [])
        ->assertSet('detectionNotice', fn ($v) => $v !== null);
});

test('saving with html_table but missing row_selector fails validation', function () {
    Livewire::test(SourcesIndex::class)
        ->set('name', 'Test')
        ->set('url', 'https://example.com')
        ->set('parser_type', 'html_table')
        ->set('parser_config', [])
        ->call('save')
        ->assertHasErrors(['parser_config.row_selector', 'parser_config.address_col', 'parser_config.port_col']);
});

test('can override detected values before saving', function () {
    Http::fake([
        'https://example.com/proxies' => Http::response("192.168.1.1:8080\n10.0.0.1:3128\n", 200),
    ]);

    Livewire::test(SourcesIndex::class)
        ->set('url', 'https://example.com/proxies')
        ->call('detectSource')
        ->assertSet('parser_type', 'plain_text')
        ->set('parser_type', 'json_api')
        ->set('name', 'Test')
        ->call('save')
        ->assertHasNoErrors();

    $source = Source::where('name', 'Test')->first();
    expect($source->parser_type->value)->toBe('json_api');
});
