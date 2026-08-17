{{-- jaunt.forms.input — see components-source/components/forms/Input.{jsx,d.ts,prompt.md} for the source contract. --}}
@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'optional' => false,
    'required' => false,
    'size' => 'md', // sm | md | lg
    'prefix' => null,
    'suffix' => null,
    'disabled' => false,
    'id' => null,
])

@php
$fieldId = $id ?? 'input-' . Str::random(8);
$heights = [
    'sm' => 'h-control-sm',
    'md' => 'h-control',
    'lg' => 'h-control-lg',
];
$wrapClasses = collect([
    'flex items-center gap-inline px-3 rounded-sm border transition-colors duration-instant ease-standard',
    $heights[$size],
    $disabled
        ? 'bg-sunken text-disabled cursor-not-allowed border-[color:var(--border-default)]'
        : ($error
            ? 'bg-card text-primary border-danger focus-within:shadow-ring-danger'
            : 'bg-card text-primary border-[color:var(--border-default)] hover:border-strong focus-within:border-focus focus-within:shadow-ring'),
])->implode(' ');
@endphp

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $fieldId }}" class="flex items-center gap-1.5 text-sm font-medium text-primary">
            {{ $label }}
            @if ($required)<span class="text-danger" aria-hidden="true">*</span>@endif
            @if ($optional)<span class="text-tertiary font-normal">optional</span>@endif
        </label>
    @endif

    <div class="{{ $wrapClasses }}">
        @if ($prefix)
            <span class="flex-none inline-flex text-tertiary">{{ $prefix }}</span>
        @endif
        {{ $attributes->merge([
            'id' => $fieldId,
            'class' => 'flex-1 min-w-0 border-0 bg-transparent outline-none text-inherit text-sm p-0 placeholder:text-tertiary disabled:cursor-not-allowed',
            'disabled' => $disabled,
        ])->merge($error ? ['aria-invalid' => 'true', 'aria-describedby' => "{$fieldId}-err"] : ($hint ? ['aria-describedby' => "{$fieldId}-hint"] : [])) }}
        @if ($suffix)
            <span class="flex-none inline-flex text-tertiary">{{ $suffix }}</span>
        @endif
    </div>

    @if ($error)
        <span id="{{ $fieldId }}-err" class="flex items-center gap-1 text-xs text-danger">{{ $error }}</span>
    @elseif ($hint)
        <span id="{{ $fieldId }}-hint" class="text-xs text-tertiary">{{ $hint }}</span>
    @endif
</div>
</content>
