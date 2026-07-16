<?php

use App\Livewire\SourcesIndex;
use App\Models\Source;
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
        ->set('showModal', true)
        ->assertSet('showModal', true);
});

test('modal resets after save', function () {
    Livewire::test(SourcesIndex::class)
        ->set('showModal', true)
        ->set('name', 'New Source')
        ->set('url', 'https://new.example.com')
        ->call('save')
        ->assertSet('showModal', false)
        ->assertSet('name', '')
        ->assertSet('url', '')
        ->assertSet('editingId', null);
});
