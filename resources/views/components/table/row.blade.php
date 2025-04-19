@props([
    'href' => null,
    'clickable' => false,
    'active' => false,
    'hover' => true,
    'selected' => false,
])

@php
    $classes = [
        'border-b border-gray-200 dark:border-gray-700',
        'transition-all duration-150 ease-in-out',
        $hover ? 'hover:bg-gray-50 dark:hover:bg-gray-800/50' : '',
        $active ? 'bg-gray-50 dark:bg-gray-800/50' : '',
        $selected ? 'bg-blue-50 dark:bg-blue-900/20' : '',
        $clickable || $href ? 'cursor-pointer hover:shadow-sm' : '',
    ];
@endphp

@if($href)
    <tr 
        onclick="window.location='{{ $href }}'" 
        {{ $attributes->merge(['class' => implode(' ', array_filter($classes))]) }}
    >
        {{ $slot }}
    </tr>
@else
    <tr 
        {{ $attributes->merge(['class' => implode(' ', array_filter($classes))]) }}
        @if($clickable) x-data="{}" x-bind:class="{ 'scale-[0.995] bg-gray-50 dark:bg-gray-800/50': $el.matches(':active') }" @endif
    >
        {{ $slot }}
    </tr>
@endif 