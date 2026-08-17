{{--
    jaunt.feedback.toast-viewport — fixed bottom-right stack that renders the
    global toast queue. Ported from ToastViewport in Toast.jsx (which just
    positions children); the queueing behavior itself is new here since the
    Blade port needs a way for any part of the app (Livewire success hooks,
    plain Alpine, anywhere) to trigger a toast without prop drilling — see
    the added `Alpine.store('toasts')` in src/js/main.js.

    Drop this ONCE near the end of the app shell layout (sibling to
    jaunt.navigation.command-palette). Trigger toasts from anywhere with:

        $store.toasts.push({ variant: 'success', title: 'Listing published.', actionLabel: 'View' })

    or from a Livewire action's browser event:

        // PHP: $this->dispatch('toast', variant: 'success', title: 'Saved.');
        // Blade (once, e.g. in the shell layout):
        // <div x-data x-on:toast.window="$store.toasts.push($event.detail)"></div>

    Auto-dismiss: 5s by default, paused while the toast (or any toast) is
    hovered; danger toasts persist until manually dismissed (duration: 0),
    matching Toast.prompt.md ("errors persist until dismissed"). Timers are
    tracked per-item in local x-data state (`timers`) since the store itself
    only needs to hold plain, serializable toast data.
--}}
@props([
    'position' => 'bottom-right', // bottom-right | top-right
])

@php
$positionCls = $position === 'top-right'
    ? 'top-5 right-5'
    : 'bottom-5 right-5';
@endphp

<div
    x-data="{
        timers: {},
        paused: false,
        schedule(toast) {
            if (!toast.duration) return;
            this.clear(toast.id);
            this.timers[toast.id] = setTimeout(() => $store.toasts.dismiss(toast.id), toast.duration);
        },
        clear(id) {
            if (this.timers[id]) { clearTimeout(this.timers[id]); delete this.timers[id]; }
        },
        pauseAll() {
            this.paused = true;
            Object.keys(this.timers).forEach((id) => this.clear(id));
        },
        resumeAll() {
            this.paused = false;
            $store.toasts.items.forEach((t) => this.schedule(t));
        },
    }"
    x-init="$watch('$store.toasts.items', (items) => { if (!paused) items.forEach((t) => { if (!(t.id in timers)) schedule(t); }); })"
    @mouseenter="pauseAll()"
    @mouseleave="resumeAll()"
    class="fixed {{ $positionCls }} z-toast flex flex-col gap-2.5"
    {{ $attributes }}
>
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-base"
            x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-instant"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="flex items-start gap-2.5 w-[340px] max-w-[calc(100vw-32px)] p-3 pl-3.5 bg-raised text-primary border-[color:var(--border-default)] border rounded-md shadow-lg"
            role="status"
            :aria-live="toast.variant === 'danger' ? 'assertive' : 'polite'"
        >
            <template x-if="toast.variant !== 'neutral'">
                <span
                    class="flex-none mt-px"
                    :class="{
                        'text-success': toast.variant === 'success',
                        'text-danger': toast.variant === 'danger',
                        'text-info': toast.variant === 'info',
                        'text-ai': toast.variant === 'ai',
                    }"
                    aria-hidden="true"
                >
                    <i
                        :data-lucide="toast.variant === 'success' ? 'check' : toast.variant === 'danger' ? 'triangle-alert' : toast.variant === 'ai' ? 'sparkles' : 'info'"
                        style="width:16px;height:16px;stroke-width:1.5"
                    ></i>
                </span>
            </template>

            <div class="flex-1 min-w-0 flex flex-col gap-0.5">
                <div class="text-sm font-medium" x-show="toast.title" x-text="toast.title"></div>
                <div class="text-xs text-secondary" x-show="toast.message" x-text="toast.message"></div>
                <button
                    type="button"
                    x-show="toast.actionLabel"
                    x-text="toast.actionLabel"
                    @click="toast.onAction && toast.onAction(); $store.toasts.dismiss(toast.id)"
                    class="self-start mt-1.5 text-xs font-semibold text-accent-text"
                ></button>
            </div>

            <button
                type="button"
                @click="clear(toast.id); $store.toasts.dismiss(toast.id)"
                aria-label="Dismiss"
                class="flex-none -mt-0.5 -mr-0.5 ml-auto p-[3px] rounded-xs text-tertiary hover:bg-hover hover:text-primary transition-colors duration-instant"
            >
                <x-jaunt.icon name="x" size="xs" />
            </button>
        </div>
    </template>
</div>
