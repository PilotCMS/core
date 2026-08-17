@php
    $listId = 'internal-links-'.str($fieldKey)
        ->append(isset($subFieldKey) ? '-'.$subFieldKey : '')
        ->append(isset($repeaterIndex) ? '-'.$repeaterIndex : '')
        ->slug();
@endphp

<div class="relative">
    <x-jaunt.icon name="link" size="sm" class="absolute left-3 top-3 text-slate-400" />
    <input
        type="text"
        inputmode="url"
        value="{{ $value }}"
        list="{{ $listId }}"
        placeholder="{{ $placeholder ?: 'Type a URL or search internal pages' }}"
        @if(isset($repeaterIndex) && isset($subFieldKey))
            wire:change="updateRepeaterField(@js($fieldKey), {{ $repeaterIndex }}, @js($subFieldKey), $event.target.value)"
        @else
            wire:change="updateField(@js($fieldKey), $event.target.value)"
        @endif
        class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-700 shadow-sm outline-none transition-[border-color,box-shadow,background-color] duration-fast placeholder:text-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
    />
    <datalist id="{{ $listId }}">
        @foreach($contentChoices as $contentChoice)
            @php $relativeUrl = $this->relativeContentUrl($contentChoice); @endphp
            <option value="{{ $relativeUrl }}" label="{{ $contentChoice->name }}{{ $contentChoice->status !== 'published' ? ' ('.$contentChoice->status.')' : '' }}">
                {{ $contentChoice->name }} {{ $relativeUrl }}
            </option>
        @endforeach
    </datalist>
</div>
