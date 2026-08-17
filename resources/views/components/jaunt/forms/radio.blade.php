{{-- jaunt.forms.radio — see components-source/components/forms/Radio.{jsx,d.ts,prompt.md} for the source contract. --}}
@props([
    'label' => null,
    'description' => null,
    'disabled' => false,
    'id' => null,
])

@php
$fieldId = $id ?? 'radio-' . Str::random(8);
@endphp

<label
    for="{{ $fieldId }}"
    class="inline-flex items-start gap-inline cursor-pointer select-none {{ $disabled ? 'cursor-not-allowed opacity-50' : '' }}"
>
    <span class="relative flex-none w-[18px] h-[18px] mt-px rounded-full border-[1.5px] border-strong bg-card grid place-items-center transition-colors
        has-[:checked]:border-accent has-[:focus-visible]:shadow-ring">
        {{ $attributes->merge([
            'type' => 'radio',
            'id' => $fieldId,
            'class' => 'peer absolute opacity-0 w-full h-full m-0 cursor-inherit',
            'disabled' => $disabled,
        ]) }}
        <span class="w-[9px] h-[9px] rounded-full bg-accent scale-0 peer-checked:scale-100 transition-transform duration-fast ease-spring"></span>
    </span>
    @if ($label || $description)
        <span class="flex flex-col gap-px">
            @if ($label)<span class="text-sm text-primary">{{ $label }}</span>@endif
            @if ($description)<span class="text-xs text-tertiary">{{ $description }}</span>@endif
        </span>
    @endif
</label>
</content>
