@props([
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white/80 dark:bg-zinc-800/80 backdrop-blur-xl shadow-macos overflow-hidden p-3 ' . $class]) }}>
    {{ $slot }}
</div>
