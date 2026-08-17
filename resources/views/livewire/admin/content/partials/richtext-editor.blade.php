@props([
    'field',
    'value' => '',
    'fieldKey' => null,
    'repeaterIndex' => null,
    'subFieldKey' => null,
])

@php
    $fieldKey ??= $field['key'] ?? '';
    $placeholder = $field['placeholder'] ?? '';
    $rows = max((int) ($field['rows'] ?? 6), 4);
    $minHeight = max($rows * 28, 132);
    $isRepeaterField = $repeaterIndex !== null && $subFieldKey !== null;
@endphp

<div
    wire:ignore
    class="pilot-richtext"
    x-bind:class="{ 'is-expanded': expanded }"
    x-bind:data-expanded="expanded ? 'true' : 'false'"
    x-on:keydown.escape.window="closeExpandedEditor()"
    x-data="pilotRichTextEditor({
        value: @js((string) $value),
        placeholder: @js($placeholder),
        fieldKey: @js($fieldKey),
        repeaterIndex: @js($repeaterIndex),
        subFieldKey: @js($subFieldKey),
        isRepeaterField: @js($isRepeaterField),
    })"
    x-init="init()"
>
    @include('livewire.admin.content.partials.richtext-toolbar')

    <div class="pilot-richtext-body">
        <div
            x-show="! sourceMode"
            x-ref="editor"
            class="pilot-richtext-surface"
            style="min-height: {{ $minHeight }}px"
            contenteditable="true"
            role="textbox"
            aria-multiline="true"
            x-bind:data-placeholder="placeholder"
            x-on:input="handleInput()"
            x-on:blur="flush()"
            x-on:keyup="refreshState()"
            x-on:mouseup="refreshState()"
            x-on:pointerup="placeCaretFromPointer($event)"
            x-on:paste.prevent="handlePaste($event)"
        ></div>

        <textarea
            x-show="sourceMode"
            x-ref="source"
            x-model="html"
            x-on:input="queueSave()"
            x-on:blur="flush()"
            rows="{{ $rows }}"
            class="pilot-richtext-source"
            spellcheck="false"
        ></textarea>

        <div class="pilot-richtext-expand-dock">
            <button
                type="button"
                x-on:click="expanded ? closeExpandedEditor() : openExpandedEditor()"
                class="pilot-richtext-expand-button"
                x-bind:title="expanded ? 'Collapse editor' : 'Expand editor'"
                x-bind:aria-label="expanded ? 'Collapse rich text editor' : 'Expand rich text editor'"
                x-bind:aria-pressed="expanded"
            >
                <span x-show="expanded"><x-jaunt.icon name="minimize-2" size="sm" /></span>
                <span x-show="! expanded"><x-jaunt.icon name="maximize-2" size="sm" /></span>
            </button>
        </div>
    </div>
</div>
