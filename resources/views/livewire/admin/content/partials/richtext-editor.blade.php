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
    style="--pilot-richtext-min-height: {{ $minHeight }}px"
    x-bind:class="{ 'is-expanded': expanded }"
    x-bind:data-expanded="expanded ? 'true' : 'false'"
    x-on:keydown.escape.window="closeExpandedEditor()"
    x-on:pilot-close-expanded-richtext.window="closeExpandedEditor($event.detail?.restoreFocus ?? true)"
    x-on:flux:editor:ready="handleReady($event)"
    x-data="pilotRichTextEditor({
        value: @js((string) $value),
        fieldKey: @js($fieldKey),
        repeaterIndex: @js($repeaterIndex),
        subFieldKey: @js($subFieldKey),
        isRepeaterField: @js($isRepeaterField),
    })"
>
    <flux:editor
        x-ref="editor"
        :value="(string) $value"
        :placeholder="$placeholder"
        class="pilot-richtext-control"
        x-on:input="handleInput()"
        x-on:blur="flush()"
    >
        <flux:editor.toolbar class="pilot-richtext-toolbar">
            <div class="pilot-richtext-toolgroup pilot-richtext-toolgroup-style">
                <flux:editor.heading />
            </div>

            <div class="pilot-richtext-toolgroup">
                <flux:editor.bold />
                <flux:editor.italic />
                <flux:editor.underline />
            </div>

            <div class="pilot-richtext-toolgroup">
                <flux:editor.bullet />
                <flux:editor.ordered />
                <flux:editor.align />
            </div>

            <div class="pilot-richtext-toolgroup">
                <flux:editor.blockquote />
                <flux:editor.link />
            </div>

            <div class="pilot-richtext-toolgroup pilot-richtext-toolgroup-history">
                <flux:editor.undo />
                <flux:editor.redo />
            </div>

            <div class="pilot-richtext-toolgroup pilot-richtext-toolgroup-action">
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
        </flux:editor.toolbar>

        <flux:editor.content />
    </flux:editor>
</div>
