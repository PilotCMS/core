{{-- jaunt.forms.checkbox — see components-source/components/forms/Checkbox.{jsx,d.ts,prompt.md} for the source contract. --}}
@props([
    'label' => null,
    'description' => null,
    'indeterminate' => false,
    'disabled' => false,
    'id' => null,
])

@php
$fieldId = $id ?? 'checkbox-' . Str::random(8);
@endphp

<label
    for="{{ $fieldId }}"
    @if ($indeterminate) x-data x-init="$el.querySelector('input').indeterminate = true" @endif
    class="inline-flex items-start gap-inline cursor-pointer select-none {{ $disabled ? 'cursor-not-allowed opacity-50' : '' }}"
>
    <span class="relative flex-none w-[18px] h-[18px] mt-px rounded-xs border-[1.5px] border-strong bg-card grid place-items-center text-on-accent transition-colors
        has-[:checked]:bg-accent has-[:checked]:border-accent has-[:indeterminate]:bg-accent has-[:indeterminate]:border-accent
        has-[:focus-visible]:shadow-ring has-[:active]:scale-90">
        {{ $attributes->merge([
            'type' => 'checkbox',
            'id' => $fieldId,
            'class' => 'peer absolute opacity-0 w-full h-full m-0 cursor-inherit',
            'disabled' => $disabled,
        ]) }}
        <svg viewBox="0 0 24 24" class="w-3 h-3 stroke-current stroke-[2.5] fill-none opacity-0 scale-[0.6] transition-all duration-fast ease-out peer-checked:opacity-100 peer-checked:scale-100 {{ $indeterminate ? 'hidden' : '' }}" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 13l4 4L19 7" />
        </svg>
        @if ($indeterminate)
            <svg viewBox="0 0 24 24" class="w-3 h-3 stroke-current stroke-[2.5] fill-none opacity-100 transition-all duration-fast ease-out" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 12h12" />
            </svg>
        @endif
    </span>
    @if ($label || $description)
        <span class="flex flex-col gap-px">
            @if ($label)<span class="text-sm text-primary">{{ $label }}</span>@endif
            @if ($description)<span class="text-xs text-tertiary">{{ $description }}</span>@endif
        </span>
    @endif
</label>
</content>
