<div class="flex h-full flex-col px-8 py-6">

    {{-- Top bar --}}
    <div class="mb-6 flex items-start justify-between">
        <div>
            <flux:heading size="xl">{{ __('Sources') }}</flux:heading>
            <flux:text class="mt-1 text-text-secondary">{{ __('Proxy source endpoints and their status') }}</flux:text>
        </div>

        <div class="flex items-center gap-2">
            @unless ($productionMode)
            <input type="file" wire:model="importFile" accept=".json" class="hidden" x-ref="importInput">
            <flux:button icon="arrow-down-tray" variant="outline" size="sm" x-on:click="$refs.importInput.click()">{{ __('Import') }}</flux:button>
            @endunless
            <flux:button icon="arrow-up-tray" variant="outline" size="sm" wire:click="exportSources">{{ __('Export') }}</flux:button>
            @unless ($productionMode)
            <flux:button icon="plus" variant="primary" wire:click="addNew">
                {{ __('Add source') }}
            </flux:button>
            @endunless
        </div>
    </div>

    {{-- Add / Edit source modal --}}
    <flux:modal wire:model="showModal">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? __('Edit source') : __('Add source') }}</flux:heading>

            <flux:field>
                <flux:label>{{ __('Name') }}</flux:label>
                <flux:input wire:model="name" placeholder="{{ __('e.g. FreeProxyList') }}" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('URL') }}</flux:label>
                <div class="flex gap-2">
                    <flux:input wire:model="url" placeholder="https://..." class="flex-1" />
                    <flux:button
                        variant="outline"
                        size="sm"
                        wire:click="detectSource"
                        wire:loading.attr="disabled"
                        wire:target="detectSource"
                    >
                        <span wire:loading.remove wire:target="detectSource">{{ __('Detect format & columns') }}</span>
                        <span wire:loading wire:target="detectSource" class="flex items-center gap-1.5">
                            <flux:icon.arrow-path class="size-3.5 animate-spin" />
                            {{ __('Detecting…') }}
                        </span>
                    </flux:button>
                </div>
                <flux:error name="url" />
            </flux:field>

            {{-- Detection notice (non-blocking, e.g. low-confidence columns) --}}
            @if ($detectionNotice)
                <flux:callout
                    variant="warning"
                    icon="exclamation-triangle"
                    heading="{{ __('Detection') }}"
                    text="{{ $detectionNotice }}"
                />
            @endif

            {{-- Detection errors (blocking, e.g. HTTP failure) --}}
            @if ($detectionError)
                <flux:callout
                    variant="danger"
                    icon="exclamation-triangle"
                    heading="{{ __('Detection') }}"
                    text="{{ $detectionError }}"
                />
            @endif

            {{-- Parser type select --}}
            <flux:field>
                <flux:label>{{ __('Parser type') }}</flux:label>
                <flux:select wire:model="parser_type">
                    <option value="">{{ __('Select a parser type…') }}</option>
                    @foreach (\App\Enums\SourceParserType::cases() as $type)
                        @if ($type->isSupported())
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endif
                    @endforeach
                </flux:select>
                <flux:error name="parser_type" />
            </flux:field>

            {{-- HtmlTable config fields --}}
            @if ($parser_type === \App\Enums\SourceParserType::HtmlTable->value)
                <div class="space-y-3 rounded-lg border border-border bg-ink p-3">
                    <flux:text class="text-[11.5px] font-medium text-text-secondary">{{ __('Column detection results') }}</flux:text>

                    <flux:field>
                        <flux:label>{{ __('Row selector') }}</flux:label>
                        <flux:input wire:model="parser_config.row_selector" placeholder="table#proxylist tr" />
                        <flux:error name="parser_config.row_selector" />
                    </flux:field>

                    <div class="grid grid-cols-2 gap-3">
                        <flux:field>
                            <flux:label>{{ __('Address column') }}</flux:label>
                            <flux:input type="number" min="0" wire:model="parser_config.address_col" placeholder="0" />
                            <flux:error name="parser_config.address_col" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Port column') }}</flux:label>
                            <flux:input type="number" min="0" wire:model="parser_config.port_col" placeholder="1" />
                            <flux:error name="parser_config.port_col" />
                        </flux:field>
                    </div>
                </div>
            @endif

            {{-- Default protocol --}}
            <flux:field>
                <flux:label>{{ __('Default protocol') }}</flux:label>
                <flux:select wire:model="default_protocol">
                    <option value="">{{ __('None (auto-detect from source)') }}</option>
                    @foreach (\App\Enums\Protocol::cases() as $protocol)
                        <option value="{{ $protocol->value }}">{{ $protocol->label() }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="cancel">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Clear all confirmation modal --}}
    <flux:modal wire:model="showClearModal">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Clear all sources') }}</flux:heading>
            <flux:text>{{ __('This will permanently delete all sources. This action cannot be undone.') }}</flux:text>
            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="$set('showClearModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="clearAll" wire:loading.attr="disabled">{{ __('Delete all') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Bulk controls --}}
    @unless ($productionMode)
    <div class="mb-4 flex items-center gap-4 rounded-xl border border-border bg-ink-panel px-5 py-3">
        <label class="flex items-center gap-2 text-[11.5px] font-medium text-text-muted">
            <span class="w-[12ch] shrink-0">{{ $allEnabled ? __('Disable all') : __('Enable all') }}</span>
            <flux:switch wire:model.live="allEnabled" class="[&[data-checked]]:bg-status-green! [&[data-checked]>span]:bg-white!" />
        </label>
        <flux:separator vertical />
        <flux:button icon="trash" variant="danger" size="sm" class="border-status-red! bg-status-red/10! text-status-red!" wire:click="$set('showClearModal', true)" wire:loading.attr="disabled">
            {{ __('Clear all sources') }}
        </flux:button>
    </div>
    @endunless

    {{-- Table --}}
    <div class="flex min-h-0 flex-1 flex-col">
    <flux:table container:class="h-full rounded-xl border border-border bg-ink-panel p-2!">
        <flux:table.columns sticky class="bg-ink-panel">
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('URL') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Active') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Enabled') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Proxies') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Last Scraped') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($sources as $source)
                <flux:table.row :key="$source->id" wire:key="source-{{ $source->id }}" class="transition-colors hover:bg-ink">
                    <flux:table.cell variant="strong">
                        <div class="flex items-center">
                            <span class="w-[24ch] shrink-0 truncate">{{ $source->name }}</span>
                            @unless ($productionMode)
                            <button
                                wire:click="edit({{ $source->id }})"
                                class="shrink-0 rounded-md p-1 text-text-muted transition-colors hover:bg-ink hover:text-accent"
                                title="{{ __('Edit') }}"
                            >
                                <flux:icon.pencil class="size-4" />
                            </button>
                            @endunless
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <span class="block max-w-xs truncate font-mono text-[11.5px] text-text-secondary">{{ $source->url }}</span>
                    </flux:table.cell>
                    <flux:table.cell align="center">
                        <span class="inline-flex transition-transform duration-200 hover:scale-110">
                            @if ($source->is_active)
                                <flux:icon.check-circle class="text-status-green" variant="solid" />
                            @else
                                <flux:icon.x-circle class="text-status-red" variant="solid" />
                            @endif
                        </span>
                    </flux:table.cell>
                    <flux:table.cell align="center">
                        @unless ($productionMode)
                        <button
                            wire:click="toggleEnabled({{ $source->id }})"
                            class="inline-flex cursor-pointer transition-transform duration-200 hover:scale-110"
                            title="{{ $source->is_enabled ? __('Click to disable') : __('Click to enable') }}"
                        >
                            @if ($source->is_enabled)
                                <flux:icon.check-circle class="text-status-green" variant="solid" />
                            @else
                                <flux:icon.x-circle class="text-status-red" variant="solid" />
                            @endif
                        </button>
                        @else
                            @if ($source->is_enabled)
                                <flux:icon.check-circle class="text-status-green" variant="solid" />
                            @else
                                <flux:icon.x-circle class="text-status-red" variant="solid" />
                            @endif
                        @endunless
                    </flux:table.cell>
                    <flux:table.cell align="center">
                        <span class="font-mono">{{ number_format($source->proxy_count) }}</span>
                    </flux:table.cell>
                    <flux:table.cell align="center">
                        <span class="inline-flex items-center gap-1.5">
                            @if ($source->last_scraped_at)
                                <span class="h-1.5 w-1.5 rounded-full bg-status-green/60"></span>
                                {{ $source->last_scraped_at->diffForHumans() }}
                            @else
                                <span class="h-1.5 w-1.5 rounded-full bg-text-muted"></span>
                                {{ __('Never') }}
                            @endif
                        </span>
                    </flux:table.cell>
                    <flux:table.cell align="center">
                        @unless ($productionMode)
                        <button
                            wire:click="delete({{ $source->id }})"
                            class="rounded-md p-1 text-text-muted transition-colors hover:bg-ink hover:text-status-red"
                            title="{{ __('Delete') }}"
                        >
                            <flux:icon.trash class="size-4" />
                        </button>
                        @endunless
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    </div>

    {{-- Footer --}}
    <div class="mt-4 flex items-center justify-between text-[11.5px] text-text-secondary">
        <span class="leading-none">{{ __('Showing :from–:to of :total', ['from' => $sources->firstItem(), 'to' => $sources->lastItem(), 'total' => $sources->total()]) }}</span>
    </div>

    <div class="mt-2">
        {{ $sources->links() }}
    </div>

</div>
