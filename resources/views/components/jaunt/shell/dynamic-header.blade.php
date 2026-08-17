{{--
    jaunt.shell.dynamic-header — view header whose title size maps directly to
    scroll position. It becomes chrome once collapsed and announces the title
    handoff with jaunt:headcollapse.
--}}
@props([
    'title' => null,
    'subtitle' => null,
    'range' => 96,
    'from' => 30,
    'to' => 17,
    'padFrom' => 22,
    'padTo' => 11,
    'top' => 'var(--topbar-h)',
    'as' => 'div',
    'scrollTarget' => null,
])

<{{ $as }}
    x-data="{
        t: 0,
        raf: 0,
        host: null,
        target: @js($scrollTarget),
        onScroll: null,
        map(v, inMin, inMax, outMin, outMax) {
            if (inMax === inMin) return outMin;
            const k = Math.min(1, Math.max(0, (v - inMin) / (inMax - inMin)));
            return outMin + k * (outMax - outMin);
        },
        get size() { return this.map(this.t, 0, 1, {{ (float) $from }}, {{ (float) $to }}) },
        get pad() { return this.map(this.t, 0, 1, {{ (float) $padFrom }}, {{ (float) $padTo }}) },
        get subOpacity() { return this.map(this.t, 0, 0.55, 1, 0) },
        get subHeight() { return this.map(this.t, 0, 0.55, 19, 0) },
        get collapsed() { return this.t > 0.98 },
        read() {
            this.raf = 0;
            const y = this.host ? this.host.scrollTop : window.scrollY;
            this.t = this.map(y, 0, {{ (float) $range }}, 0, 1);
        },
        init() {
            if (this.target) this.host = document.querySelector(this.target);
            let n = this.$el.parentElement;
            while (!this.host && n) {
                const oy = getComputedStyle(n).overflowY;
                if ((oy === 'auto' || oy === 'scroll') && n.scrollHeight > n.clientHeight) { this.host = n; break; }
                n = n.parentElement;
            }
            this.onScroll = () => { if (!this.raf) this.raf = requestAnimationFrame(() => this.read()); };
            this.read();
            (this.host || window).addEventListener('scroll', this.onScroll, { passive: true });
            this.$watch('collapsed', (collapsed) => this.$dispatch('jaunt:headcollapse', { collapsed }));
            this.$dispatch('jaunt:headcollapse', { collapsed: this.collapsed });
        },
        destroy() {
            (this.host || window).removeEventListener('scroll', this.onScroll);
            if (this.raf) cancelAnimationFrame(this.raf);
        },
    }"
    :data-collapsed="collapsed"
    :style="{
        paddingTop: pad + 'px',
        paddingBottom: pad + 'px',
        borderBottomColor: collapsed ? 'var(--material-hairline)' : 'transparent',
        boxShadow: collapsed ? 'var(--material-edge)' : 'none',
    }"
    {{ $attributes->merge([
        'class' => 'j-material-chrome sticky z-[5] flex items-end gap-3 px-[var(--pad-view)] border-b border-b-transparent',
        'style' => "top: {$top};",
    ]) }}
>
    <div class="min-w-0 flex-1">
        <div class="truncate font-semibold leading-[var(--lh-tight)] tracking-[var(--ls-tight)] text-primary" :style="{ fontSize: size + 'px' }">
            {{ $title }}
        </div>
        @if ($subtitle)
            <div
                class="truncate text-sm text-secondary"
                :style="{ opacity: subOpacity, height: subHeight + 'px', marginTop: (subOpacity * 3) + 'px' }"
            >
                {{ $subtitle }}
            </div>
        @endif
    </div>
    @if (isset($actions) || $slot->isNotEmpty())
        <div class="flex shrink-0 gap-2">{{ $actions ?? $slot }}</div>
    @endif
</{{ $as }}>
