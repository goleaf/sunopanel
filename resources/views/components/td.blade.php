@props(['align' => 'left'])

@php
    $alignmentClasses = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right',
    ];
    
    $classes = 'px-4 py-3 whitespace-nowrap text-sm ' . $alignmentClasses[$align];
@endphp

<td {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</td> 