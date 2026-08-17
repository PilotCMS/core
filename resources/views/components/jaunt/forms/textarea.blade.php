{{-- jaunt.forms.textarea — see components-source/components/forms/Textarea.{jsx,d.ts,prompt.md} for the source contract. --}}
@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'optional' => false,
    'maxLength' => null,
    'id' => null,
    'value' => null,
])

@php
$fieldId = $id ?? 'textarea-' . Str::random(8);
$len = strlen((string) ($value ?? $attributes->get('value') ?? ''));
@endphp

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $fieldId }}" class="flex items-center gap-1.5 text-sm font-medium text-primary">
            {{ $label }}
            @if ($required)<span class="text-danger" aria-hidden="true">*</span>@endif
            @if ($optional)<span class="text-tertiary font-normal">optional</span>@endif
        </label>
    @endif

    <textarea
        {{ $attributes->merge([
            'id' => $fieldId,
            'maxlength' => $maxLength,
            'class' => 'w-full min-h-[80px] resize-y px-3 py-2 rounded-sm border text-sm leading-normal outline-none transition-colors duration-instant ease-standard
                bg-card text-primary placeholder:text-tertiary
                border-[color:var(--border-default)] hover:border-strong focus:border-focus focus:shadow-ring
                disabled:bg-sunken disabled:text-disabled disabled:cursor-not-allowed'
                . ($error ? ' !border-danger focus:!shadow-ring-danger' : ''),
        ]) }}
        @if ($error) aria-invalid="true" @endif
    >{{ $value }}</textarea>

    <div class="flex items-center justify-between">
        @if ($error)
            <span class="text-xs text-danger">{{ $error }}</span>
        @elseif ($hint)
            <span class="text-xs text-tertiary">{{ $hint }}</span>
        @else
            <span></span>
        @endif
        @if ($maxLength)
            <span class="text-xs text-tertiary ml-auto">{{ $len }}/{{ $maxLength }}</span>
        @endif
    </div>
</div>
</content>
