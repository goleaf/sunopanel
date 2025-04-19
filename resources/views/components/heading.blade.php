@props([
    'title' => '',
    'breadcrumbs' => [],
    'level' => 2,
])

@php
    $element = 'h' . $level;
    
    $baseClasses = 'font-semibold text-gray-900';
    
    $sizeClasses = [
        '1' => 'text-2xl md:text-3xl lg:text-4xl',
        '2' => 'text-xl md:text-2xl lg:text-3xl',
        '3' => 'text-lg md:text-xl lg:text-2xl',
        '4' => 'text-base md:text-lg lg:text-xl',
        '5' => 'text-sm md:text-base lg:text-lg',
        '6' => 'text-xs md:text-sm lg:text-base',
    ][$level] ?? 'text-xl';
    
    $classes = $baseClasses . ' ' . $sizeClasses;
@endphp

<{{ $element }} {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $element }}> 