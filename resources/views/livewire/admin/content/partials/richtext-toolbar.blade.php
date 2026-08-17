<div class="pilot-richtext-toolbar" role="toolbar" aria-label="Text formatting">
    <div class="pilot-richtext-style">
        <select
            class="pilot-richtext-style-trigger"
            x-bind:value="active.block"
            x-on:change="formatBlock($event.target.value)"
            aria-label="Text style"
            title="Text style"
        >
            @foreach ([['p', 'Body'], ['blockquote', 'Quote'], ['h2', 'Heading 2'], ['h3', 'Heading 3'], ['h4', 'Heading 4'], ['h5', 'Heading 5'], ['h6', 'Heading 6']] as [$tag, $label])
                <option value="{{ $tag }}">{{ $label }}</option>
            @endforeach
        </select>
        <x-jaunt.icon name="chevron-down" size="sm" />
    </div>

    <span class="pilot-richtext-divider" aria-hidden="true"></span>

    <button type="button" x-on:click="runCommand('bold')" class="pilot-richtext-button" x-bind:class="{ 'is-active': active.bold }" x-bind:aria-pressed="active.bold" title="Bold (⌘B)" aria-label="Bold">
        <x-jaunt.icon name="bold" size="sm" />
    </button>
    <button type="button" x-on:click="runCommand('italic')" class="pilot-richtext-button" x-bind:class="{ 'is-active': active.italic }" x-bind:aria-pressed="active.italic" title="Italic (⌘I)" aria-label="Italic">
        <x-jaunt.icon name="italic" size="sm" />
    </button>
    <button type="button" x-on:click="runCommand('underline')" class="pilot-richtext-button" x-bind:class="{ 'is-active': active.underline }" x-bind:aria-pressed="active.underline" title="Underline (⌘U)" aria-label="Underline">
        <x-jaunt.icon name="underline" size="sm" />
    </button>
    <button type="button" x-on:click="createLink()" class="pilot-richtext-button" x-bind:class="{ 'is-active': active.link }" x-bind:aria-pressed="active.link" title="Add link" aria-label="Add link">
        <x-jaunt.icon name="link" size="sm" />
    </button>
    <button type="button" x-on:click="runCommand('insertUnorderedList')" class="pilot-richtext-button" x-bind:class="{ 'is-active': active.ul }" x-bind:aria-pressed="active.ul" title="Bulleted list" aria-label="Bulleted list">
        <x-jaunt.icon name="list" size="sm" />
    </button>
    <button type="button" x-on:click="runCommand('insertOrderedList')" class="pilot-richtext-button" x-bind:class="{ 'is-active': active.ol }" x-bind:aria-pressed="active.ol" title="Numbered list" aria-label="Numbered list">
        <x-jaunt.icon name="list-ordered" size="sm" />
    </button>

    <span class="pilot-richtext-divider" aria-hidden="true"></span>

    <button type="button" x-on:click="runCommand('justifyLeft')" class="pilot-richtext-button" x-bind:class="{ 'is-active': active.align === 'left' }" x-bind:aria-pressed="active.align === 'left'" title="Align left" aria-label="Align left">
        <x-jaunt.icon name="align-left" size="sm" />
    </button>
    <button type="button" x-on:click="runCommand('justifyCenter')" class="pilot-richtext-button" x-bind:class="{ 'is-active': active.align === 'center' }" x-bind:aria-pressed="active.align === 'center'" title="Align center" aria-label="Align center">
        <x-jaunt.icon name="align-center" size="sm" />
    </button>
    <button type="button" x-on:click="runCommand('justifyRight')" class="pilot-richtext-button" x-bind:class="{ 'is-active': active.align === 'right' }" x-bind:aria-pressed="active.align === 'right'" title="Align right" aria-label="Align right">
        <x-jaunt.icon name="align-right" size="sm" />
    </button>

    <span class="pilot-richtext-spacer"></span>

    <button type="button" x-on:click="toggleSource()" class="pilot-richtext-button" x-bind:class="{ 'is-active': sourceMode }" x-bind:aria-pressed="sourceMode" title="Edit HTML" aria-label="Edit HTML source">
        <x-jaunt.icon name="code" size="sm" />
    </button>

</div>
