{{--
    jaunt.feedback.spinner — circular loader for buttons and inline waits.
    Split out of Progress.jsx (which exports both Progress and Spinner from
    one file) since Blade components are one-per-file.
--}}
@props([
    'size' => 18, // px
    'variant' => 'default', // default | ai
])

@php
$topColor = $variant === 'ai' ? 'border-t-ai' : 'border-t-accent';
@endphp

<span
    {{ $attributes->merge([
        'class' => "inline-block rounded-full border-[2.5px] border-sunken {$topColor} j-spinner",
    ]) }}
    style="width: {{ $size }}px; height: {{ $size }}px;"
    role="status"
    aria-label="Loading"
></span>

@once
    <style>
        .j-spinner {
            animation: j-spinner-spin 0.7s linear infinite;
        }
        @keyframes j-spinner-spin {
            to { transform: rotate(360deg); }
        }
    </style>
@endonce
