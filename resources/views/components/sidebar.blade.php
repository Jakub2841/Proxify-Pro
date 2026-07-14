<nav class="flex h-screen w-64 shrink-0 flex-col border-r-2 border-border bg-ink-sidebar font-sans">

    {{-- Logo --}}
    <div class="flex items-center gap-2.5 px-6 py-5">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-accent bg-ink-panel">
            <flux:icon.squares-2x2 class="size-4 text-accent" variant="outline" />
        </div>
        <span class="font-mono text-lg font-medium tracking-tight text-text-primary">
            Proxify<span class="text-accent"> Pro</span>
        </span>
    </div>

    <div class="mx-6 border-t-2 border-text-muted"></div>

    {{-- Nav --}}
    <flux:navlist class="px-4 py-4 text-base">
        @foreach ($navItems as $item)
            <flux:navlist.item
                :href="$item['url']"
                :icon="$item['icon']"
                :current="$item['active']"
            >
                {{ $item['label'] }}
            </flux:navlist.item>
        @endforeach
    </flux:navlist>

</nav>
