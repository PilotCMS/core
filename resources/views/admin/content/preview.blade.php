<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="pilot-content-id" content="{{ $content->id }}">
    <title>{{ $content->name }} Preview</title>
    @vite(['resources/css/app.css'])
    <style>
        [data-pilot-editable="block"] {
            position: relative;
            outline: 1px solid transparent;
            outline-offset: 3px;
            cursor: pointer;
        }

        [data-pilot-editable="block"]:hover {
            outline-color: rgb(45 212 191);
            background-color: rgb(240 253 250 / 0.45);
        }

        [data-pilot-editable="block"][data-pilot-selected="true"] {
            outline: 2px solid rgb(20 184 166);
            box-shadow: 0 0 0 6px rgb(20 184 166 / 0.12);
        }

        [data-pilot-editable="block"]::before {
            content: attr(data-pilot-component);
            position: absolute;
            top: -28px;
            left: 0;
            z-index: 20;
            display: none;
            border-radius: 6px;
            background: rgb(15 23 42);
            padding: 4px 8px;
            color: white;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            pointer-events: none;
        }

        [data-pilot-editable="block"]:hover::before,
        [data-pilot-editable="block"][data-pilot-selected="true"]::before {
            display: block;
        }

        .pilot-preview-toolbar {
            position: absolute;
            top: -34px;
            right: 0;
            z-index: 30;
            display: none;
            align-items: center;
            gap: 2px;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            background: var(--surface-card);
            padding: 2px;
            box-shadow: var(--shadow-md);
        }

        [data-pilot-editable="block"]:hover > .pilot-preview-toolbar,
        [data-pilot-editable="block"][data-pilot-selected="true"] > .pilot-preview-toolbar {
            display: flex;
        }

        .pilot-preview-toolbar button {
            display: flex;
            height: 30px;
            min-width: 30px;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-full);
            color: var(--text-secondary);
            transition: background-color var(--dur-fast) var(--ease-standard), color var(--dur-fast) var(--ease-standard), box-shadow var(--dur-fast) var(--ease-standard), transform var(--dur-instant) var(--ease-spring);
        }

        .pilot-preview-toolbar button:hover {
            background: var(--surface-hover);
            color: var(--text-primary);
        }

        .pilot-preview-toolbar button:focus-visible {
            outline: none;
            box-shadow: var(--ring);
        }

        .pilot-preview-toolbar button:active {
            transform: scale(.94);
        }

        .pilot-preview-toolbar button[data-pilot-action="delete"]:hover {
            background: var(--danger-subtle);
            color: var(--danger);
        }
    </style>
</head>
<body class="min-h-full bg-slate-50 text-slate-900 antialiased" data-pilot-content-id="{{ $content->id }}">
    <main class="mx-auto max-w-6xl p-8 lg:p-12">
        @if($blocks->isEmpty())
            <div class="rounded-xl border-2 border-dashed border-slate-200 bg-white p-14 text-center text-slate-500">
                <p class="text-lg font-semibold text-slate-700">No blocks yet</p>
                <p class="mt-1 text-sm">Add blocks from the editor to preview this page.</p>
            </div>
        @else
            <div class="space-y-8">
                @foreach($blocks as $block)
                    @php
                        $componentName = (string) str($block->type)->replace(['.', '/', '\\'], '-')->kebab();
                        $componentView = 'components.' . $componentName;
                        $data = $block->data ?? [];
                        $children = $block->children ?? [];
                    @endphp
                    <section
                        data-preview-block="{{ $block->id }}"
                        data-pilot-editable="block"
                        data-pilot-block-id="{{ $block->id }}"
                        data-pilot-component="{{ $block->type }}"
                        data-pilot-component-path="{{ $content->type }}/{{ $block->type }}"
                        class="rounded-lg border border-transparent transition-colors hover:border-blue-300 hover:bg-blue-50/30"
                    >
                        <div class="pilot-preview-toolbar" role="group" aria-label="Block actions">
                            <button type="button" data-pilot-action="move-up" aria-label="Move block up" title="Move block up"><x-jaunt.icon name="arrow-up" size="xs" /></button>
                            <button type="button" data-pilot-action="move-down" aria-label="Move block down" title="Move block down"><x-jaunt.icon name="arrow-down" size="xs" /></button>
                            <button type="button" data-pilot-action="duplicate" aria-label="Duplicate block" title="Duplicate block"><x-jaunt.icon name="copy" size="xs" /></button>
                            <button type="button" data-pilot-action="delete" aria-label="Delete block" title="Delete block"><x-jaunt.icon name="trash-2" size="xs" /></button>
                        </div>
                        @if(view()->exists($componentView))
                            <x-dynamic-component :component="$componentName" :block="$block" :data="$data" :children="$children" />
                        @else
                            <x-fallback :block="$block" :data="$data" :children="$children" />
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </main>

    @includeIf('pilot::editor-bridge')
    @includeIf('pilot::in-context')
</body>
</html>
