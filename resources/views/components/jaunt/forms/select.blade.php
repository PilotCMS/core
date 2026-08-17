{{--
    jaunt.forms.select — see components-source/components/forms/Select.{jsx,d.ts,prompt.md}
    for the source contract. The source renders a styled native <select> (accessible,
    keyboard-complete by default) rather than a custom listbox — no Alpine open/closed
    state needed, matching the .jsx 1:1.
--}}
@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'placeholder' => null,
    'options' => [], // array of strings or ['value' => ..., 'label' => ...]
    'size' => 'md', // sm | md | lg
    'required' => false,
    'optional' => false,
    'disabled' => false,
    'id' => null,
    'value' => null,
])

@php
$fieldId = $id ?? 'select-' . Str::random(8);
$heights = [
    'sm' => 'h-control-sm',
    'md' => 'h-control',
    'lg' => 'h-control-lg',
];
$isPlaceholder = ($value ?? $attributes->get('value') ?? '') === '';
@endphp

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $fieldId }}" class="flex items-center gap-1.5 text-sm font-medium text-primary">
            {{ $label }}
            @if ($required)<span class="text-danger" aria-hidden="true">*</span>@endif
            @if ($optional)<span class="text-tertiary font-normal">optional</span>@endif
        </label>
    @endif

    <div class="relative flex items-center">
        <select
            {{ $attributes->merge([
                'id' => $fieldId,
                'disabled' => $disabled,
                'class' => "appearance-none w-full {$heights[$size]} pl-3 pr-[34px] rounded-sm border text-sm cursor-pointer outline-none transition-colors duration-instant ease-standard
                    bg-card text-primary
                    border-[color:var(--border-default)] hover:border-strong focus:border-focus focus:shadow-ring
                    disabled:bg-sunken disabled:text-disabled disabled:cursor-not-allowed"
                    . ($isPlaceholder ? ' text-tertiary' : '')
                    . ($error ? ' !border-danger focus:!shadow-ring-danger' : ''),
            ]) }}
            @if ($error) aria-invalid="true" @endif
        >
            @if ($placeholder)
                <option value="" disabled>{{ $placeholder }}</option>
            @endif
            @foreach ($options as $option)
                @php
                    $opt = is_string($option) ? ['value' => $option, 'label' => $option] : $option;
                @endphp
                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
            @endforeach
        </select>
        <span class="absolute right-2.5 pointer-events-none inline-flex text-tertiary">
            <x-jaunt.icon name="chevron-down" size="sm" />
        </span>
    </div>

    @if ($error)
        <span class="text-xs text-danger">{{ $error }}</span>
    @elseif ($hint)
        <span class="text-xs text-tertiary">{{ $hint }}</span>
    @endif
</div>
</content>
