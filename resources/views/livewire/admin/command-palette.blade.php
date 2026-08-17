<div
    x-data="{
        open: false,
        activeIndex: 0,
        openPalette() {
            this.open = true;
            this.activeIndex = 0;
            this.$nextTick(() => this.$refs.search?.focus());
        },
        closePalette() {
            this.open = false;
            this.$wire.set('search', '');
        },
        results() {
            return Array.from(this.$el.querySelectorAll('[data-command-result]'));
        },
        move(delta) {
            const results = this.results();

            if (results.length === 0) {
                return;
            }

            this.activeIndex = (this.activeIndex + delta + results.length) % results.length;
            results[this.activeIndex]?.scrollIntoView({ block: 'nearest' });
        },
        choose() {
            this.results()[this.activeIndex]?.click();
        },
    }"
    x-on:open-command-palette.window="openPalette()"
    x-on:keydown.window="
        if (($event.metaKey || $event.ctrlKey) && $event.key.toLowerCase() === 'k') {
            $event.preventDefault();
            openPalette();
        }

        if (open && $event.key === 'Escape') {
            $event.preventDefault();
            closePalette();
        }
    "
>
    <div
        x-cloak
        x-show="open"
        class="fixed inset-0 z-[90] flex items-start justify-center bg-overlay px-4 pb-4 pt-[12vh] backdrop-blur-[3px]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="command-palette-title"
        x-transition.opacity
        wire:ignore.self
    >
        <button type="button" class="absolute inset-0 cursor-default" aria-label="Close search" x-on:click="closePalette()"></button>

        <div
            class="relative z-10 w-full max-w-[600px] overflow-hidden rounded-lg bg-raised shadow-xl outline outline-1 -outline-offset-1 outline-subtle"
            x-on:click.outside="closePalette()"
            x-transition.scale.origin.top.duration.150ms
        >
            <h2 id="command-palette-title" class="sr-only">Command search</h2>

            <div class="flex items-center gap-3 border-b border-subtle px-[18px] py-4">
                <x-jaunt.icon name="search" size="md" class="text-tertiary" />
                <input
                    x-ref="search"
                    type="search"
                    wire:model.live.debounce.150ms="search"
                    x-on:input="activeIndex = 0"
                    x-on:keydown.arrow-down.prevent="move(1)"
                    x-on:keydown.arrow-up.prevent="move(-1)"
                    x-on:keydown.enter.prevent="choose()"
                    placeholder="Search content, assets, settings..."
                    class="min-w-0 flex-1 border-0 bg-transparent p-0 text-lg font-medium tracking-[var(--ls-snug)] text-primary outline-none placeholder:text-tertiary focus:ring-0"
                />
                <button type="button" class="shrink-0 rounded-xs bg-sunken px-[7px] py-[3px] font-mono text-2xs text-tertiary" x-on:click="closePalette()">
                    esc
                </button>
            </div>

            <div class="max-h-[54vh] overflow-y-auto p-2">
                @forelse ($groups as $group)
                    <div>
                        <div class="px-2.5 pb-[5px] pt-[11px] text-2xs font-medium uppercase tracking-[var(--ls-caps)] text-tertiary">
                            {{ $group['label'] }}
                        </div>

                        <div>
                            @foreach ($group['results'] as $result)
                                <a
                                    href="{{ $result['url'] }}"
                                    wire:navigate
                                    data-command-result
                                    x-on:mouseenter="activeIndex = Array.from($el.closest('[role=dialog]').querySelectorAll('[data-command-result]')).indexOf($el)"
                                    x-bind:data-active="activeIndex === Array.from($el.closest('[role=dialog]').querySelectorAll('[data-command-result]')).indexOf($el)"
                                    class="group flex min-h-[46px] items-center gap-3 rounded-md px-2.5 py-[7px] !text-primary no-underline outline-none transition-colors hover:bg-active hover:no-underline data-[active=true]:bg-active"
                                >
                                    <span class="grid h-[30px] w-[30px] shrink-0 place-items-center rounded-md bg-sunken text-secondary group-data-[active=true]:bg-card group-data-[active=true]:text-primary">
                                        <x-jaunt.icon :name="$result['icon']" size="sm" />
                                    </span>
                                    <span class="flex min-w-0 flex-1 flex-col gap-px">
                                        <span class="block truncate text-base font-medium tracking-[var(--ls-snug)]">{{ $result['title'] }}</span>
                                        <span class="block truncate text-xs text-tertiary">{{ $result['description'] }}</span>
                                    </span>
                                    <span class="grid h-5 w-5 shrink-0 place-items-center text-tertiary opacity-0 group-data-[active=true]:opacity-100" aria-hidden="true">
                                        <x-jaunt.icon name="corner-down-left" size="sm" />
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-[34px] text-center">
                        <p class="text-sm text-tertiary">No results found</p>
                        <p class="mt-1 text-xs text-tertiary">Try a page title, asset name, block key, or admin section.</p>
                    </div>
                @endforelse
            </div>

            <div class="flex items-center gap-3.5 border-t border-subtle px-4 py-[9px] text-2xs text-tertiary">
                <span><b class="font-mono font-normal text-secondary">↑</b> <b class="font-mono font-normal text-secondary">↓</b> to navigate</span>
                <span><b class="font-mono font-normal text-secondary">↵</b> to select</span>
                <span class="ml-auto">Jaunt</span>
            </div>
        </div>
    </div>
</div>
