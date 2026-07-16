<div class="flex h-full flex-col overflow-y-auto px-8 py-6">

    {{-- Top bar --}}
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Settings') }}</flux:heading>
        <flux:text class="mt-1 text-text-secondary">{{ __('Configure scraper and system preferences.') }}</flux:text>
    </div>

    <div class="max-w-2xl space-y-8">

        <div>
            <p class="mb-4 text-[11px] font-semibold uppercase tracking-wider text-accent">{{ __('Scraping') }}</p>
            <div class="divide-y divide-border-soft rounded-xl border border-border bg-ink-panel">
                <div class="flex items-center gap-6 px-5 py-3.5">
                    <span class="w-40 shrink-0 text-[11.5px] font-medium text-text-secondary">{{ __('Scrape interval') }}</span>
                    <div class="flex items-center gap-1.5 font-mono text-sm">
                        <input type="number" min="1" wire:model="scrapeInterval" class="w-16 rounded-md border border-border bg-ink px-2 py-1 text-right text-sm text-text-primary focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/30" />
                        <span class="text-text-muted">min</span>
                    </div>
                </div>
                <div class="flex items-center gap-6 px-5 py-3.5">
                    <span class="w-40 shrink-0 text-[11.5px] font-medium text-text-secondary">{{ __('Check interval') }}</span>
                    <div class="flex items-center gap-1.5 font-mono text-sm">
                        <input type="number" min="1" wire:model="checkInterval" class="w-16 rounded-md border border-border bg-ink px-2 py-1 text-right text-sm text-text-primary focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/30" />
                        <span class="text-text-muted">min</span>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <p class="mb-4 text-[11px] font-semibold uppercase tracking-wider text-accent">{{ __('Thresholds') }}</p>
            <div class="rounded-xl border border-border bg-ink-panel">
                <div class="flex items-center gap-6 px-5 py-3.5">
                    <span class="w-40 shrink-0 text-[11.5px] font-medium text-text-secondary">{{ __('Max latency') }}</span>
                    <div class="flex items-center gap-1.5 font-mono text-sm">
                        <input type="number" min="1" wire:model="maxLatency" class="w-20 rounded-md border border-border bg-ink px-2 py-1 text-right text-sm text-text-primary focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/30" />
                        <span class="text-text-muted">ms</span>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <p class="mb-4 text-[11px] font-semibold uppercase tracking-wider text-accent">{{ __('API') }}</p>
            <div class="rounded-xl border border-border bg-ink-panel">
                <div class="flex items-center justify-between px-5 py-3.5">
                    <span class="text-[11.5px] font-medium text-text-secondary">{{ __('Public API access') }}</span>
                    <flux:switch wire:model="apiAccess" class="[&[data-checked]]:bg-status-green! [&[data-checked]>span]:bg-white!" />
                </div>
            </div>
        </div>

        <div>
            <p class="mb-4 text-[11px] font-semibold uppercase tracking-wider text-accent">{{ __('Database') }}</p>
            <div class="divide-y divide-border-soft rounded-xl border border-border bg-ink-panel">
                <div class="flex items-center gap-6 px-5 py-3.5">
                    <span class="w-40 shrink-0 text-[11.5px] font-medium text-text-secondary">{{ __('Auto-clean') }}</span>
                    <select wire:model="autoCleanPeriod" class="rounded-md border border-border bg-ink px-2 py-1 font-mono text-sm text-text-primary focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/30">
                        @foreach ($cleanupPeriods as $period)
                            <option value="{{ $period->value }}">{{ $period->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center justify-between px-5 py-3.5">
                    <span class="text-[11.5px] font-medium text-text-secondary">{{ __('Save inactive proxies') }}</span>
                    <flux:switch wire:model="saveInactive" class="[&[data-checked]]:bg-status-green! [&[data-checked]>span]:bg-white!" />
                </div>
            </div>
        </div>

    </div>

    <div class="mt-8 max-w-2xl flex justify-end">
        <flux:button variant="primary" wire:click="save">{{ __('Save changes') }}</flux:button>
    </div>

</div>
