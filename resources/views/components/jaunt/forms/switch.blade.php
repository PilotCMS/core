{{-- jaunt.forms.switch — see components-source/components/forms/Switch.{jsx,d.ts,prompt.md} for the source contract. --}}
@props([
    'label' => null,
    'size' => 'md', // sm | md
    'disabled' => false,
    'id' => null,
])

@php
$fieldId = $id ?? 'switch-' . Str::random(8);
$trackSizes = [
    'sm' => 'w-7 h-4',
    'md' => 'w-[34px] h-5',
];
$thumbSizes = [
    'sm' => 'w-3 h-3 peer-checked:translate-x-3',
    'md' => 'w-4 h-4 peer-checked:translate-x-[14px]',
];
@endphp

<label
    for="{{ $fieldId }}"
    class="inline-flex items-center gap-inline cursor-pointer select-none {{ $disabled ? 'cursor-not-allowed opacity-50' : '' }}"
>
    <span class="relative flex-none rounded-full bg-[color:var(--border-strong)] transition-colors duration-fast ease-standard has-[:checked]:bg-accent has-[:focus-visible]:shadow-ring {{ $trackSizes[$size] }}">
        {{ $attributes->merge([
            'type' => 'checkbox',
            'role' => 'switch',
            'id' => $fieldId,
            'class' => 'peer absolute opacity-0 w-full h-full m-0 cursor-inherit',
            'disabled' => $disabled,
        ]) }}
        <span class="absolute top-0.5 left-0.5 rounded-full bg-white shadow-sm transition-transform duration-fast ease-spring {{ $thumbSizes[$size] }}"></span>
    </span>
    @if ($label)
        <span class="text-sm text-primary">{{ $label }}</span>
    @endif
</label>
</content>
