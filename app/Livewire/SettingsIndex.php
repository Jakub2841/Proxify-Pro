<?php

namespace App\Livewire;

use App\Enums\CleanupPeriod;
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

    #[Validate('integer|min:1')]
    public int $checkInterval = 15;

    #[Validate('integer|min:1')]
    public int $maxLatency = 2000;

    public bool $apiAccess = true;

    #[Validate('integer|min:0')]
    public int $autoCleanPeriod = 30;

    public bool $saveInactive = false;

    public function mount(): void
    {
        $this->scrapeInterval = Setting::get('scrape_interval', 30);
        $this->checkInterval = Setting::get('check_interval', 15);
        $this->maxLatency = Setting::get('max_latency', 2000);
        $this->apiAccess = Setting::get('api_access', true);
        $this->autoCleanPeriod = Setting::get('auto_clean_period', 30);
        $this->saveInactive = Setting::get('save_inactive', false);
    }

    public function save(): void
    {
        $this->validate();

        Setting::put('scrape_interval', $this->scrapeInterval);
        Setting::put('check_interval', $this->checkInterval);
        Setting::put('max_latency', $this->maxLatency);
        Setting::put('api_access', $this->apiAccess);
        Setting::put('auto_clean_period', $this->autoCleanPeriod);
        Setting::put('save_inactive', $this->saveInactive);

        Flux::toast(__('Settings saved'), variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.settings-index', [
            'cleanupPeriods' => CleanupPeriod::cases(),
        ]);
    }
}
