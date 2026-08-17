@props([
    'status',
])

@if ($status)
    <div {{ $attributes->merge(['class' => 'auth-session-status']) }} role="status">
        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="m5.75 10.25 2.5 2.5 6-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>{{ $status }}</span>
    </div>
@endif
