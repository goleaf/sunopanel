@props(['href' => '#', 'icon' => null, 'label' => '', 'color' => 'indigo'])

@php
$colors = [
    'indigo' => 'text-indigo-600 hover:text-indigo-900',
    'red' => 'text-red-600 hover:text-red-900',
    'green' => 'text-green-600 hover:text-green-900',
    'blue' => 'text-blue-600 hover:text-blue-900',
    'yellow' => 'text-yellow-600 hover:text-yellow-900',
    'gray' => 'text-gray-600 hover:text-gray-900',
];

$colorClass = $colors[$color] ?? $colors['indigo'];
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-1 text-sm $colorClass transition-colors duration-150"]) }}>
    @if($icon)
        <span class="mr-1">
            {!! $icon !!}
        </span>
    @endif
    {{ $label }}
</a> 