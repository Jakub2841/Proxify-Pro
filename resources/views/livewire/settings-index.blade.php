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
                        <input type="number" min="1" wire:model="scrapeInterval" @disabled($productionMode) class="w-16 rounded-md border border-border bg-ink px-2 py-1 text-right text-sm text-text-primary focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/30" />
                        <span class="text-text-muted">min</span>
                    </div>
                    <div class="ml-auto">
                        <flux:switch wire:model="scrapeEnabled" :disabled="$productionMode" class="[&[data-checked]]:bg-status-green! [&[data-checked]>span]:bg-white!" />
                    </div>
                </div>
                <div class="flex items-center gap-6 px-5 py-3.5">
                    <span class="w-40 shrink-0 text-[11.5px] font-medium text-text-secondary">{{ __('Check interval') }}</span>
                    <div class="flex items-center gap-1.5 font-mono text-sm">
                        <input type="number" min="1" wire:model="checkInterval" @disabled($productionMode) class="w-16 rounded-md border border-border bg-ink px-2 py-1 text-right text-sm text-text-primary focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/30" />
                        <span class="text-text-muted">min</span>
                    </div>
                    <div class="ml-auto">
                        <flux:switch wire:model="checkEnabled" :disabled="$productionMode" class="[&[data-checked]]:bg-status-green! [&[data-checked]>span]:bg-white!" />
                    </div>
                </div>
                <div class="flex items-center justify-between px-5 py-3.5">
                    <span class="text-[11.5px] font-medium text-text-secondary">{{ __('Remove not passing proxies') }}</span>
                    <flux:switch wire:model="saveInactive" :disabled="$productionMode" class="[&[data-checked]]:bg-status-green! [&[data-checked]>span]:bg-white!" />
                </div>
            </div>
        </div>

        <div>
            <p class="mb-4 text-[11px] font-semibold uppercase tracking-wider text-accent">{{ __('Thresholds') }}</p>
            <div class="rounded-xl border border-border bg-ink-panel">
                <div class="flex items-center gap-6 px-5 py-3.5">
                    <span class="w-40 shrink-0 text-[11.5px] font-medium text-text-secondary">{{ __('Max latency') }}</span>
                    <div class="flex items-center gap-1.5 font-mono text-sm">
                        <input type="number" min="1" wire:model="maxLatency" @disabled($productionMode) class="w-20 rounded-md border border-border bg-ink px-2 py-1 text-right text-sm text-text-primary focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/30" />
                        <span class="text-text-muted">ms</span>
                    </div>
                </div>
                <div class="flex items-center justify-between px-5 py-3.5">
                    <span class="text-[11.5px] font-medium text-text-secondary">{{ __('Queue workers') }}</span>
                    <span class="text-text-muted text-[11px]">{{ __('Adjust numprocs in supervisor.conf, then restart') }}</span>
                </div>
            </div>
        </div>

        <div>
            <p class="mb-4 text-[11px] font-semibold uppercase tracking-wider text-accent">{{ __('API') }}</p>
            <div class="rounded-xl border border-border bg-ink-panel">
                <div class="flex items-center justify-between px-5 py-3.5">
                    <span class="text-[11.5px] font-medium text-text-secondary">{{ __('Public API access') }}</span>
                    <flux:switch wire:model="apiAccess" :disabled="$productionMode" class="[&[data-checked]]:bg-status-green! [&[data-checked]>span]:bg-white!" />
                </div>
            </div>
        </div>

        <div>
            <p class="mb-4 text-[11px] font-semibold uppercase tracking-wider text-accent">{{ __('Database') }}</p>
            <div class="rounded-xl border border-border bg-ink-panel">
                <div class="flex items-center justify-between px-5 py-3.5">
                    <span class="text-[11.5px] font-medium text-text-secondary">{{ __('Clear all proxies') }}</span>
                    <flux:button variant="danger" size="sm" wire:click="$set('showClearDbModal', true)" :disabled="$productionMode">{{ __('Clear database') }}</flux:button>
                </div>
            </div>
        </div>

    </div>

    {{-- Clear database confirmation modal --}}
    <flux:modal wire:model="showClearDbModal">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Clear database') }}</flux:heading>
            <flux:text>{{ __('This will permanently delete all proxy entries. This action cannot be undone.') }}</flux:text>
            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="$set('showClearDbModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="clearDatabase">{{ __('Delete all proxies') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <div class="mt-8 max-w-2xl flex justify-end">
        @unless ($productionMode)
        <flux:button variant="primary" wire:click="save">{{ __('Save changes') }}</flux:button>
        @endunless
    </div>

</div>
