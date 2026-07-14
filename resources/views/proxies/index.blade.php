@extends('layouts.app')

@section('content')
<div class="px-8 py-6">

    {{-- Top bar --}}
    <div class="mb-6 flex items-start justify-between">
        <div>
            <flux:heading size="xl">{{ __('Proxies') }}</flux:heading>
            <flux:text class="mt-1 text-text-secondary">{{ __('Scraped, validated and ready to export') }}</flux:text>
        </div>

        <div class="flex items-center gap-3">
            <flux:input icon="magnifying-glass" placeholder="{{ __('Search ip:port') }}" class="w-64 transition-shadow duration-200 focus-within:ring-1 focus-within:ring-accent/30!" />
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
        {{-- Row 1: protocol + checks --}}
        <div class="flex items-center gap-2 border-b border-border-soft pb-3" x-data="{
            activeProtocols: [],
            activeChecks: [],
            toggle(arr, val) { arr.includes(val) ? arr.splice(arr.indexOf(val), 1) : arr.push(val) }
        }">
            <span class="mr-2 text-[11px] font-medium text-text-muted">{{ __('Protocol') }}</span>

            @foreach ($protocols as $protocol)
                <button
                    @click="toggle(activeProtocols, '{{ $protocol->value }}')"
                    :class="activeProtocols.includes('{{ $protocol->value }}')
                        ? 'rounded-full border border-status-green bg-status-green/10 px-3.5 py-1.5 text-[11.5px] font-medium text-status-green'
                        : 'rounded-full border border-border px-3.5 py-1.5 text-[11.5px] font-medium text-text-secondary hover:border-text-muted hover:text-text-primary'"
                    class="rounded-full border border-border px-3.5 py-1.5 text-[11.5px] font-medium text-text-secondary transition-colors duration-200"
                >{{ $protocol->label() }}</button>
            @endforeach

            <flux:separator vertical />

            <span class="mr-2 text-[11px] font-medium text-text-muted">{{ __('Checks') }}</span>

            @foreach ($checks as $check)
                <button
                    @click="toggle(activeChecks, '{{ $check->value }}')"
                    :class="activeChecks.includes('{{ $check->value }}')
                        ? 'rounded-full border border-status-green bg-status-green/10 px-3.5 py-1.5 text-[11.5px] font-medium text-status-green'
                        : 'rounded-full border border-border px-3.5 py-1.5 text-[11.5px] font-medium text-text-secondary hover:border-text-muted hover:text-text-primary'"
                    class="rounded-full border border-border px-3.5 py-1.5 text-[11.5px] font-medium text-text-secondary transition-colors duration-200"
                >{{ $check->label() }}</button>
            @endforeach

            <flux:button icon="arrow-down-tray" variant="primary" size="sm" class="ml-auto!">{{ __('Export list') }}</flux:button>
        </div>

        {{-- Row 2: country + sort --}}
        <div class="flex items-center justify-between pt-3">
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-medium text-text-muted">{{ __('Country') }}</span>
                <select class="rounded-lg border border-border bg-ink px-3 py-1.5 text-[11.5px] text-text-secondary transition-colors duration-200 hover:border-text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/30">
                    <option value="">{{ __('All countries') }}</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country['alpha2'] }}">{{ $country['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-[11px] font-medium text-text-muted">
                    {{ __('Active only') }} <flux:switch class="[&[data-checked]]:bg-status-green! [&[data-checked]>span]:bg-white!" />
                </label>

                <span class="text-[11px] font-medium text-text-muted">{{ __('Sort') }}</span>
                <select class="rounded-lg border border-border bg-ink px-3 py-1.5 text-[11.5px] text-text-secondary transition-colors duration-200 hover:border-text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent/30">
                    @foreach ($sortOptions as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Address') }}</flux:table.column>
            <flux:table.column>{{ __('Protocol') }}</flux:table.column>
            <flux:table.column>{{ __('Country') }}</flux:table.column>
            <flux:table.column>{{ __('Google') }}</flux:table.column>
            <flux:table.column>{{ __('Cloudflare') }}</flux:table.column>
            <flux:table.column>{{ __('Latency') }}</flux:table.column>
            <flux:table.column>{{ __('Checked') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($proxies as $proxy)
                <flux:table.row :key="$proxy['address']">
                    <flux:table.cell variant="strong">{{ $proxy['address'] }}</flux:table.cell>
                    <flux:table.cell>{{ $proxy['protocol'] }}</flux:table.cell>
                    <flux:table.cell>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-3 w-4 rounded-xs bg-ink brightness-150"></span>
                            {{ $proxy['country'] }}
                        </span>
                    </flux:table.cell>
                    <flux:table.cell>
                        <span class="inline-flex transition-transform duration-200 hover:scale-110">
                            @if ($proxy['google'])
                                <flux:icon.check-circle class="text-status-green" variant="solid" />
                            @else
                                <flux:icon.x-circle class="text-status-red" variant="solid" />
                            @endif
                        </span>
                    </flux:table.cell>
                    <flux:table.cell>
                        <span class="inline-flex transition-transform duration-200 hover:scale-110">
                            @if ($proxy['cloudflare'])
                                <flux:icon.check-circle class="text-status-green" variant="solid" />
                            @else
                                <flux:icon.x-circle class="text-status-red" variant="solid" />
                            @endif
                        </span>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-1.5">
                            <span class="flex items-end gap-0.5">
                                @for ($i = 1; $i <= 4; $i++)
                                    <span class="w-[3px] rounded-full transition-all duration-300 {{ $i <= $proxy['bars'] ? 'bg-accent' : 'bg-border' }}" style="height: {{ 3 + $i * 2 }}px"></span>
                                @endfor
                            </span>
                            <span>{{ $proxy['latency'] ? $proxy['latency'] . ' ms' : '—' }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-status-green/60"></span>
                            {{ $proxy['checked'] }}
                        </span>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{-- Footer --}}
    <div class="mt-4 flex items-center justify-between text-[11.5px] text-text-secondary">
        <span>{{ __('Showing 1–1 of 1') }}</span>
        <span class="inline-flex items-center gap-1.5">
            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-status-green"></span>
            {{ __('Next scrape in 00:00') }}
        </span>
    </div>

</div>
@endsection
