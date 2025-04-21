@props(['sortable' => false, 'direction' => null])

<th {{ $attributes->merge(['class' => 'px-4 py-3 bg-base-200/60 text-left text-xs font-semibold text-base-content uppercase tracking-wider']) }}>
    @if ($sortable)
        <div class="flex items-center gap-1 cursor-pointer">
            {{ $slot }}
            
            @if ($direction === 'asc')
                <x-icon name="chevron-up" size="4" />
            @elseif ($direction === 'desc')
                <x-icon name="chevron-down" size="4" />
            @else
                <x-icon name="chevron-updown" size="4" class="opacity-30" />
            @endif
        </div>
    @else
        {{ $slot }}
    @endif
</th> 