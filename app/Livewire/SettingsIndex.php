<?php

namespace App\Livewire;

use App\Models\Proxy;
use App\Models\Setting;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class SettingsIndex extends Component
{
    #[Validate('integer|min:1')]
    public int $scrapeInterval = 30;

    public bool $scrapeEnabled = true;

    #[Validate('integer|min:1')]
    public int $checkInterval = 15;

    public bool $checkEnabled = true;

    #[Validate('integer|min:1')]
    public int $maxLatency = 2000;

    public bool $apiAccess = true;

    public bool $saveInactive = false;

    public bool $showClearDbModal = false;

    public function clearDatabase(): void
    {
        if ($this->isProduction()) {
            return;
        }

        Proxy::query()->delete();

        $this->showClearDbModal = false;

        Flux::toast(__('All proxies deleted.'), variant: 'success');
    }

    public function mount(): void
    {
        $this->scrapeInterval = Setting::get('scrape_interval', 30);
        $this->scrapeEnabled = Setting::get('scrape_enabled', true);
        $this->checkInterval = Setting::get('check_interval', 15);
        $this->checkEnabled = Setting::get('check_enabled', true);
        $this->maxLatency = Setting::get('max_latency', 2000);
        $this->apiAccess = Setting::get('api_access', true);
        $this->saveInactive = Setting::get('save_inactive', false);
    }

    public function save(): void
    {
        if ($this->isProduction()) {
            return;
        }

        $this->validate();

        Setting::put('scrape_interval', $this->scrapeInterval);
        Setting::put('scrape_enabled', $this->scrapeEnabled);
        Setting::put('check_interval', $this->checkInterval);
        Setting::put('check_enabled', $this->checkEnabled);
        Setting::put('max_latency', $this->maxLatency);
        Setting::put('api_access', $this->apiAccess);
        Setting::put('save_inactive', $this->saveInactive);

        Flux::toast(__('Settings saved'), variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.settings-index', [
            'productionMode' => $this->isProduction(),
        ]);
    }

    private function isProduction(): bool
    {
        return config('app.production_mode', false);
    }
}
