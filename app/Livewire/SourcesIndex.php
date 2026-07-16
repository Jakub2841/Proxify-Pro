<?php

namespace App\Livewire;

use App\Models\Source;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SourcesIndex extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|url|max:2048')]
    public string $url = '';

    public bool $allEnabled;

    public function mount(): void
    {
        $this->allEnabled = Source::where('is_enabled', false)->doesntExist();
    }

    public function toggleEnabled(int $id): void
    {
        Source::where('id', $id)->update(['is_enabled' => \DB::raw('NOT is_enabled')]);
    }

    public function edit(int $id): void
    {
        $source = Source::findOrFail($id);

        $this->editingId = $id;
        $this->name = $source->name;
        $this->url = $source->url;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            Source::findOrFail($this->editingId)->update([
                'name' => $this->name,
                'url' => $this->url,
            ]);
        } else {
            Source::create([
                'name' => $this->name,
                'url' => $this->url,
            ]);
        }

        $this->reset(['showModal', 'editingId', 'name', 'url']);
    }

    public function delete(int $id): void
    {
        Source::findOrFail($id)->delete();
    }

    public function updatedAllEnabled(bool $value): void
    {
        Source::query()->update(['is_enabled' => $value]);
    }

    public bool $showClearModal = false;

    public function clearAll(): void
    {
        Source::query()->delete();

        $this->showClearModal = false;
    }

    public function render(): View
    {
        return view('livewire.sources-index', [
            'sources' => Source::latest('last_scraped_at')->paginate(20),
        ]);
    }
}
