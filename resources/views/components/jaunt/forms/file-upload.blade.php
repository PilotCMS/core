{{--
    jaunt.forms.file-upload — see components-source/components/forms/FileUpload.{jsx,d.ts,prompt.md}
    for the source contract. Drag state is client-only (Alpine); file handling is left to the
    caller via a native `change`/`drop` listener (e.g. `x-on:jaunt-files.window="..."` or wire:model
    on the hidden input) since a static primitive can't own upload logic itself.
--}}
@props([
    'hint' => 'PNG, JPG or WebP up to 25 MB',
    'accept' => null,
    'multiple' => false,
    'id' => null,
])

@php
$fieldId = $id ?? 'file-upload-' . Str::random(8);
@endphp

<div
    x-data="{ drag: false }"
    @dragover.prevent="drag = true"
    @dragleave.prevent="drag = false"
    @drop.prevent="drag = false; $refs.{{ $fieldId }}.files = $event.dataTransfer.files; $refs.{{ $fieldId }}.dispatchEvent(new Event('change', { bubbles: true }))"
    @click="$refs.{{ $fieldId }}.click()"
    @keydown.enter.prevent="$refs.{{ $fieldId }}.click()"
    @keydown.space.prevent="$refs.{{ $fieldId }}.click()"
    role="button"
    tabindex="0"
    {{ $attributes->merge([
        'class' => 'flex flex-col items-center justify-center gap-2 px-5 py-7 text-center rounded-lg border-[1.5px] border-dashed border-strong bg-sunken text-secondary cursor-pointer transition-colors duration-instant ease-standard
            hover:border-[color:var(--accent-border)] hover:bg-hover
            focus-visible:outline-none focus-visible:shadow-ring',
    ]) }}
    :class="drag ? 'border-focus bg-brand-subtle text-brand-text' : ''"
>
    <span class="grid place-items-center w-10 h-10 rounded-full bg-card border-[color:var(--border-default)] border text-secondary">
        <x-jaunt.icon name="upload" size="sm" />
    </span>
    <span class="text-sm font-medium text-primary">
        <b class="font-semibold text-accent-text">Click to upload</b> or drag and drop
    </span>
    <span class="text-xs text-tertiary">{{ $hint }}</span>
    <input
        type="file"
        x-ref="{{ $fieldId }}"
        id="{{ $fieldId }}"
        class="hidden"
        @if ($accept) accept="{{ $accept }}" @endif
        @if ($multiple) multiple @endif
    />
</div>
</content>
