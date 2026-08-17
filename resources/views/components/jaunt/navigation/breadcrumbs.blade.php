{{-- jaunt.navigation.breadcrumbs — last item is the current page --}}
@props(['items' => []])

<nav {{ $attributes->merge(['class' => 'flex items-center gap-0.5 text-sm min-w-0']) }} aria-label="Breadcrumb">
    @foreach ($items as $i => $item)
        @php $last = $i === count($items) - 1; @endphp
        <a
            href="{{ $item['href'] ?? '#' }}"
            @if ($last) aria-current="page" @endif
            class="inline-flex items-center gap-1.5 px-1.5 py-0.5 rounded-sm whitespace-nowrap transition-colors duration-instant
                {{ $last ? 'text-primary font-medium pointer-events-none' : 'text-secondary hover:text-primary hover:bg-hover' }}"
        >
            @if (!empty($item['icon']))
                <x-jaunt.icon :name="$item['icon']" size="sm" />
            @endif
            {{ $item['label'] }}
        </a>
        @unless ($last)
            <span class="text-disabled inline-flex" aria-hidden="true">
                <x-jaunt.icon name="chevron-right" size="sm" />
            </span>
        @endunless
    @endforeach
</nav>
