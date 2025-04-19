@props([
    'sortable' => false,
    'column' => '',
    'direction' => null
])

@php
    $classes = 'px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
    
    if ($sortable) {
        $classes .= ' cursor-pointer hover:bg-gray-100';
    }
@endphp

@if ($sortable)
    <th 
        {{ $attributes->merge(['class' => $classes]) }}
        wire:click="sort('{{ $column }}')"
    >
        <div class="flex items-center space-x-1">
            <span>{{ $slot }}</span>
            
            @if ($direction === 'asc' && $column === request('sort'))
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                </svg>
            @elseif ($direction === 'desc' && $column === request('sort'))
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            @endif
        </div>
    </th>
@else
    <th {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </th>
@endif 