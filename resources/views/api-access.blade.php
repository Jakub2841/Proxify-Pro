@extends('layouts.app')

@section('content')
<div class="flex h-full flex-col overflow-y-auto px-8 py-6">

    {{-- Header --}}
    <div class="mb-8">
        <flux:heading size="xl">{{ __('API Access') }}</flux:heading>
        <flux:text class="mt-1 text-text-secondary">{{ __('No auth required. Just plug in and go.') }}</flux:text>
    </div>

    {{-- Quick start --}}
    <div class="mb-8 rounded-xl border border-accent/30 bg-accent/5 px-6 py-5">
        <div class="flex items-center gap-2 mb-3">
            <flux:icon.bolt class="size-5 text-accent" />
            <flux:heading size="base" class="text-accent">{{ __('Quick start') }}</flux:heading>
        </div>
        <div class="rounded-lg bg-ink px-4 py-3 font-mono text-sm text-text-primary">
            curl "{{ url('/api/proxies') }}"
        </div>
    </div>

    {{-- Two-column layout for endpoints + examples --}}
    <div class="grid grid-cols-2 gap-6 mb-8">

        {{-- JSON endpoint --}}
        <div class="rounded-xl border border-border bg-ink-panel">
            <div class="border-b border-border-soft px-5 py-3">
                <div class="flex items-center gap-2">
                    <span class="rounded bg-status-green/10 px-2 py-0.5 font-mono text-[11px] font-medium text-status-green">GET</span>
                    <code class="font-mono text-sm text-text-primary">/api/proxies</code>
                </div>
            </div>
            <div class="px-5 py-4">
                <p class="mb-3 text-[11.5px] text-text-secondary">{{ __('Paginated JSON with all proxy details. Accepts optional filters.') }}</p>

                <p class="mb-2 text-[11px] font-medium text-text-muted">{{ __('Try it') }}</p>
                <div class="mb-4 rounded-lg bg-ink px-3 py-2 font-mono text-[11px] text-text-secondary">
                    curl "{{ url('/api/proxies?country=DE&protocols[]=https') }}"
                </div>

                <details class="group">
                    <summary class="cursor-pointer text-[11px] font-medium text-accent hover:text-accent/80">{{ __('View response format') }}</summary>
                    <pre class="mt-3 overflow-x-auto rounded-lg bg-ink p-3 font-mono text-[10.5px] text-text-secondary leading-relaxed">{
  "data": [
    {
      "address": "1.1.1.1",
      "port": 8080,
      "protocol": "https",
      "anonymity": "elite",
      "country": "DE",
      "google_pass": true,
      "cloudflare_pass": false,
      "latency_ms": 50,
      "last_checked_at": "2026-07-16T10:00:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 42
  }
}</pre>
                </details>
            </div>
        </div>

        {{-- TXT endpoint --}}
        <div class="rounded-xl border border-border bg-ink-panel">
            <div class="border-b border-border-soft px-5 py-3">
                <div class="flex items-center gap-2">
                    <span class="rounded bg-status-green/10 px-2 py-0.5 font-mono text-[11px] font-medium text-status-green">GET</span>
                    <code class="font-mono text-sm text-text-primary">/api/proxies/txt</code>
                </div>
            </div>
            <div class="px-5 py-4">
                <p class="mb-3 text-[11.5px] text-text-secondary">{{ __('Plain text list, one proxy per line. Active-only enforced.') }}</p>

                <p class="mb-2 text-[11px] font-medium text-text-muted">{{ __('Try it') }}</p>
                <div class="mb-4 rounded-lg bg-ink px-3 py-2 font-mono text-[11px] text-text-secondary">
                    curl "{{ url('/api/proxies/txt?protocols[]=socks5') }}"
                </div>

                <details class="group">
                    <summary class="cursor-pointer text-[11px] font-medium text-accent hover:text-accent/80">{{ __('View response format') }}</summary>
                    <pre class="mt-3 overflow-x-auto rounded-lg bg-ink p-3 font-mono text-[10.5px] text-text-secondary leading-relaxed">https://1.1.1.1:8080
http://2.2.2.2:3128
socks5://3.3.3.3:1080</pre>
                </details>
            </div>
        </div>

    </div>

    {{-- Filter reference --}}
    <div class="mb-8 rounded-xl border border-border bg-ink-panel">
        <div class="border-b border-border-soft px-5 py-3">
            <div class="flex items-center gap-2">
                <flux:icon.funnel class="size-4 text-text-muted" />
                <flux:heading size="base">{{ __('Filter Reference') }}</flux:heading>
            </div>
        </div>
        <div class="overflow-x-auto px-5 py-4">
            <table class="w-full text-[11.5px]">
                <thead>
                    <tr class="border-b border-border-soft text-left text-text-muted">
                        <th class="pb-3 pr-4 font-medium">{{ __('Parameter') }}</th>
                        <th class="pb-3 pr-4 font-medium">{{ __('Type') }}</th>
                        <th class="pb-3 pr-4 font-medium">{{ __('Values') }}</th>
                        <th class="pb-3 font-medium">{{ __('Notes') }}</th>
                    </tr>
                </thead>
                <tbody class="text-text-secondary">
                    <tr class="border-b border-border-soft/50">
                        <td class="py-2 pr-4 font-mono text-text-primary">protocols[]</td>
                        <td class="py-2 pr-4">array</td>
                        <td class="py-2 pr-4 font-mono">https, http, socks5, socks4</td>
                        <td class="py-2">{{ __('Multi-select') }}</td>
                    </tr>
                    <tr class="border-b border-border-soft/50">
                        <td class="py-2 pr-4 font-mono text-text-primary">anonymity[]</td>
                        <td class="py-2 pr-4">array</td>
                        <td class="py-2 pr-4 font-mono">elite, anonymous, transparent</td>
                        <td class="py-2">{{ __('Multi-select') }}</td>
                    </tr>
                    <tr class="border-b border-border-soft/50">
                        <td class="py-2 pr-4 font-mono text-text-primary">checks[]</td>
                        <td class="py-2 pr-4">array</td>
                        <td class="py-2 pr-4 font-mono">google, cloudflare</td>
                        <td class="py-2">{{ __('AND logic') }}</td>
                    </tr>
                    <tr class="border-b border-border-soft/50">
                        <td class="py-2 pr-4 font-mono text-text-primary">country</td>
                        <td class="py-2 pr-4">string</td>
                        <td class="py-2 pr-4 font-mono">DE, US, JP…</td>
                        <td class="py-2">{{ __('ISO alpha2') }}</td>
                    </tr>
                    <tr class="border-b border-border-soft/50">
                        <td class="py-2 pr-4 font-mono text-text-primary">active_only</td>
                        <td class="py-2 pr-4">boolean</td>
                        <td class="py-2 pr-4 font-mono">1</td>
                        <td class="py-2">{{ __('JSON only; TXT always active') }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 font-mono text-text-primary">per_page</td>
                        <td class="py-2 pr-4">integer</td>
                        <td class="py-2 pr-4 font-mono">20 (default), 50, 100</td>
                        <td class="py-2">{{ __('JSON only') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Rate limiting --}}
    <div class="flex items-start gap-4 rounded-xl border border-border bg-ink-panel px-5 py-4">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-status-red/10">
            <flux:icon.shield-exclamation class="size-4 text-status-red" />
        </div>
        <div>
            <flux:heading size="base" class="mb-1">{{ __('Rate Limiting') }}</flux:heading>
            <p class="text-[11.5px] text-text-secondary">
                {{ __('60 requests per minute per IP. Exceeding returns:') }}
                <code class="ml-1 rounded bg-ink px-1.5 py-0.5 font-mono text-[11px] text-status-red">429 Failed, try again later</code>
            </p>
        </div>
    </div>

</div>
@endsection
