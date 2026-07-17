<div class="flex h-full flex-col px-8 py-6">

    {{-- Top bar --}}
    <div class="mb-6 flex items-start justify-between">
        <div>
            <flux:heading size="xl">{{ __('Proxies') }}</flux:heading>
            <flux:text class="mt-1 text-text-secondary">{{ __('Scraped, validated and ready to export') }}</flux:text>
        </div>

        <div class="flex items-center gap-3">
            <flux:input icon="magnifying-glass" placeholder="{{ __('Search ip:port') }}" wire:model.live.debounce.300ms="search" class="w-64 transition-shadow duration-200 focus-within:ring-1 focus-within:ring-accent/30!" />
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="mb-6 grid grid-cols-4 gap-4">
        @foreach ($stats as $stat)
            <div class="group rounded-xl border border-border bg-ink-panel px-5 py-4 transition-all duration-200 hover:border-text-muted hover:bg-ink-panel/80">
                <p class="text-[11.5px] font-medium tracking-wide text-text-secondary transition-colors duration-200 group-hover:text-text-secondary/80">{{ $stat['label'] }}</p>
                <p class="mt-2 font-mono text-xl transition-all duration-300 {{ ($stat['accent'] ?? false) ? 'text-status-green' : 'text-text-primary' }}">
                    {{ $stat['value'] }}
                </p>
            </div>
        @endforeach
    </div>

    {{-- Filter bar --}}
    <div class="mb-6 rounded-xl border border-border bg-ink-panel px-5 py-3">
        <div class="flex items-center gap-2 border-b border-border-soft pb-3">
            <span class="mr-2 text-[11px] font-medium text-text-muted">{{ __('Protocol') }}</span>

            @foreach ($protocols as $protocol)
                <button
                    wire:click="toggleProtocol('{{ $protocol->value }}')"
                    @class([
                        'rounded-full border px-3.5 py-1.5 text-[11.5px] font-medium transition-colors duration-200',
                        'border-status-green bg-status-green/10 text-status-green' => in_array($protocol->value, $activeProtocols, true),
                        'border-border text-text-secondary hover:border-text-muted hover:text-text-primary' => ! in_array($protocol->value, $activeProtocols, true),
                    ])
                >{{ $protocol->label() }}</button>
            @endforeach

            <flux:separator vertical />

            <span class="mr-2 text-[11px] font-medium text-text-muted">{{ __('Anonymity') }}</span>

            @foreach ($anonymityLevels as $level)
                <button
                    wire:click="toggleAnonymity('{{ $level->value }}')"
                    @class([
                        'rounded-full border px-3.5 py-1.5 text-[11.5px] font-medium transition-colors duration-200',
                        'border-status-green bg-status-green/10 text-status-green' => in_array($level->value, $activeAnonymity, true),
                        'border-border text-text-secondary hover:border-text-muted hover:text-text-primary' => ! in_array($level->value, $activeAnonymity, true),
                    ])
                >{{ $level->label() }}</button>
            @endforeach

            <flux:separator vertical />

            <span class="mr-2 text-[11px] font-medium text-text-muted">{{ __('Checks') }}</span>

            @foreach ($checks as $check)
                <button
                    wire:click="toggleCheck('{{ $check->value }}')"
                    @class([
                        'rounded-full border px-3.5 py-1.5 text-[11.5px] font-medium transition-colors duration-200',
                        'border-status-green bg-status-green/10 text-status-green' => in_array($check->value, $activeChecks, true),
                        'border-border text-text-secondary hover:border-text-muted hover:text-text-primary' => ! in_array($check->value, $activeChecks, true),
                    ])
                >{{ $check->label() }}</button>
            @endforeach

            <flux:button icon="arrow-path" variant="outline" size="sm" class="ml-auto!" wire:click="scrapeSources" wire:loading.attr="disabled" wire:target="scrapeSources">{{ __('Scrape now') }}</flux:button>
            <flux:button icon="arrow-path" variant="outline" size="sm">{{ __('Check now') }}</flux:button>
            <flux:button icon="arrow-down-tray" variant="primary" size="sm" wire:click="$set('showExportModal', true)">{{ __('Export') }}</flux:button>
        </div>

        <div class="flex items-center justify-between pt-3">
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-medium text-text-muted">{{ __('Country') }}</span>
                <select wire:change="applyCountry($event.target.value)" class="rounded-lg border border-border bg-ink px-3 py-1.5 text-[11.5px] text-text-secondary transition-colors duration-200 hover:border-text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/30">
                    <option value="">{{ __('All countries') }}</option>
                    @foreach ($countries as $c)
                        <option value="{{ $c['alpha2'] }}" @selected($country === $c['alpha2'])>{{ $c['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-[11px] font-medium text-text-muted">
                    {{ __('Active only') }} <flux:switch wire:model="activeOnly" class="[&[data-checked]]:bg-status-green! [&[data-checked]>span]:bg-white!" />
                </label>

                <span class="text-[11px] font-medium text-text-muted">{{ __('Sort') }}</span>
                <select wire:change="applySort($event.target.value)" class="rounded-lg border border-border bg-ink px-3 py-1.5 text-[11.5px] text-text-secondary transition-colors duration-200 hover:border-text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/30">
                    @foreach ($sortOptions as $option)
                        <option value="{{ $option->value }}" @selected($sort === $option->value)>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Export modal --}}
    <flux:modal wire:model="showExportModal">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Export proxies') }}</flux:heading>

            <flux:field>
                <flux:label>{{ __('Format') }}</flux:label>
                <div class="flex gap-2">
                    <flux:button wire:click="$set('exportFormat', 'csv')" variant="{{ $exportFormat === 'csv' ? 'primary' : 'outline' }}" size="sm">CSV</flux:button>
                    <flux:button wire:click="$set('exportFormat', 'txt')" variant="{{ $exportFormat === 'txt' ? 'primary' : 'outline' }}" size="sm">TXT</flux:button>
                    <flux:button wire:click="$set('exportFormat', 'json')" variant="{{ $exportFormat === 'json' ? 'primary' : 'outline' }}" size="sm">JSON</flux:button>
                </div>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Data format') }}</flux:label>
                <div class="flex gap-2">
                    <flux:button wire:click="$set('exportDataFormat', 'ip_port')" variant="{{ $exportDataFormat === 'ip_port' ? 'primary' : 'outline' }}" size="sm">IP:Port</flux:button>
                    <flux:button wire:click="$set('exportDataFormat', 'protocol_ip_port')" variant="{{ $exportDataFormat === 'protocol_ip_port' ? 'primary' : 'outline' }}" size="sm">{{ __('Protocol://IP:Port') }}</flux:button>
                </div>
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="$set('showExportModal', false)">{{ __('Cancel') }}</flux:button>
                <a href="{{ $this->exportUrl() }}" class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-4 py-2 text-sm font-medium text-ink transition-colors hover:bg-accent/80">
                    <flux:icon.arrow-down-tray class="size-4" />{{ __('Download') }}
                </a>
            </div>
        </div>
    </flux:modal>

    {{-- Table --}}
    <div class="flex min-h-0 flex-1 flex-col" wire:loading.class="opacity-50 transition-opacity">
    <flux:table container:class="h-full rounded-xl border border-border bg-ink-panel p-2!">
        <flux:table.columns sticky class="bg-ink-panel">
            <flux:table.column>{{ __('Address') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Protocol') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Anonymity') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Country') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Google') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Cloudflare') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Latency') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Checked') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($proxies as $proxy)
                <flux:table.row :key="$proxy->id" wire:key="proxy-{{ $proxy->id }}" class="transition-colors hover:bg-ink">
                    <flux:table.cell variant="strong">
                        <div class="flex items-center" x-data="{ copied: false }">
                            <span class="font-mono w-[21ch] shrink-0">{{ $proxy->address }}:{{ $proxy->port }}</span>
                            <button
                                @click="() => {
                                    const text = '{{ $proxy->address }}:{{ $proxy->port }}';
                                    if (navigator.clipboard) {
                                        navigator.clipboard.writeText(text);
                                    } else {
                                        const el = document.createElement('textarea');
                                        el.value = text;
                                        document.body.appendChild(el);
                                        el.select();
                                        document.execCommand('copy');
                                        document.body.removeChild(el);
                                    }
                                    copied = true;
                                    setTimeout(() => copied = false, 1500);
                                }"
                                class="shrink-0 rounded-md p-1 text-text-muted transition-colors hover:bg-ink hover:text-accent"
                                title="{{ __('Copy address') }}"
                            >
                                <flux:icon.clipboard class="size-4" x-show="!copied" />
                                <flux:icon.check class="size-4 text-status-green" x-show="copied" x-cloak />
                            </button>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell align="center">{{ $proxy->protocol->label() }}</flux:table.cell>
                    <flux:table.cell align="center">{{ $proxy->anonymity?->label() ?? '—' }}</flux:table.cell>
                    <flux:table.cell align="center">
                        <span class="inline-flex items-center gap-1.5">
                            <span>{{ $proxy->country_flag }}</span>
                            {{ $proxy->country }}
                        </span>
                    </flux:table.cell>
                    <flux:table.cell align="center">
                        <span class="inline-flex transition-transform duration-200 hover:scale-110">
                            @if ($proxy->google_pass)
                                <flux:icon.check-circle class="text-status-green" variant="solid" />
                            @else
                                <flux:icon.x-circle class="text-status-red" variant="solid" />
                            @endif
                        </span>
                    </flux:table.cell>
                    <flux:table.cell align="center">
                        <span class="inline-flex transition-transform duration-200 hover:scale-110">
                            @if ($proxy->cloudflare_pass)
                                <flux:icon.check-circle class="text-status-green" variant="solid" />
                            @else
                                <flux:icon.x-circle class="text-status-red" variant="solid" />
                            @endif
                        </span>
                    </flux:table.cell>
                    <flux:table.cell align="center">
                        <div class="flex items-center justify-center gap-1.5">
                            <span class="flex items-end gap-0.5">
                                @for ($i = 1; $i <= 4; $i++)
                                    <span class="w-[3px] rounded-full transition-all duration-300 {{ $i <= $proxy->latency_bars ? 'bg-accent' : 'bg-border' }}" style="height: {{ 3 + $i * 2 }}px"></span>
                                @endfor
                            </span>
                            <span>{{ $proxy->latency_ms ? $proxy->latency_ms . ' ms' : '—' }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell align="center">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-status-green/60"></span>
                            {{ $proxy->last_checked_at?->diffForHumans() }}
                        </span>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    </div>

    {{-- Footer --}}
    <div class="mt-4 flex items-center justify-between text-[11.5px] text-text-secondary">
        <span class="leading-none">{{ __('Showing :from–:to of :total', ['from' => $proxies->firstItem(), 'to' => $proxies->lastItem(), 'total' => $proxies->total()]) }}</span>
        <span class="inline-flex items-center gap-1.5 leading-none">
            <span class="h-1.5 w-1.5 shrink-0 animate-pulse rounded-full bg-status-green"></span>
            {{ __('Next scrape in 00:00') }}
        </span>
    </div>

    <div class="mt-2">
        {{ $proxies->links() }}
    </div>

</div>