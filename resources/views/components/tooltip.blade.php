@props([
    'text' => '',
    'position' => 'top', // top, bottom, left, right
    'color' => 'neutral', // primary, secondary, accent, neutral, info, success, warning, error
    'width' => 'auto',
    'class' => '',
    'triggerClass' => '',
    'interactive' => false,
])

@php
    $positions = [
        'top' => 'tooltip-top',
        'bottom' => 'tooltip-bottom',
        'left' => 'tooltip-left',
        'right' => 'tooltip-right',
    ];
    
    $colors = [
        'primary' => 'tooltip-primary',
        'secondary' => 'tooltip-secondary',
        'accent' => 'tooltip-accent',
        'neutral' => '',
        'info' => 'tooltip-info',
        'success' => 'tooltip-success',
        'warning' => 'tooltip-warning',
        'error' => 'tooltip-error',
    ];
    
    $tooltipPosition = $positions[$position] ?? 'tooltip-top';
    $tooltipColor = $colors[$color] ?? '';

    // Create a unique ID for the tooltip if it's interactive
    $tooltipId = $interactive ? 'tooltip-' . \Illuminate\Support\Str::random(6) : '';
@endphp

@if($interactive)
    <div
        x-data="{ open: false }"
        x-on:mouseenter="open = true"
        x-on:mouseleave="open = true"
        x-on:click="open = !open"
        class="relative inline-block {{ $triggerClass }}"
        aria-describedby="{{ $tooltipId }}"
    >
        {{ $slot }}
        
        <div
            id="{{ $tooltipId }}"
            x-cloak
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 p-2 text-sm bg-base-200 rounded shadow-lg {{ $class }}"
            style="width: {{ $width }};"
            x-on:click.outside="open = false"
            role="tooltip"
            :class="{
                'bottom-full left-1/2 transform -translate-x-1/2 mb-2': '{{ $position }}' === 'top',
                'top-full left-1/2 transform -translate-x-1/2 mt-2': '{{ $position }}' === 'bottom',
                'right-full top-1/2 transform -translate-y-1/2 mr-2': '{{ $position }}' === 'left',
                'left-full top-1/2 transform -translate-y-1/2 ml-2': '{{ $position }}' === 'right',
            }"
        >
            {!! $text !!}
            @if(isset($tooltipContent))
                {{ $tooltipContent }}
            @endif
        </div>
    </div>
@else
    <div class="tooltip {{ $tooltipPosition }} {{ $tooltipColor }} {{ $class }}" data-tip="{{ $text }}">
        {{ $slot }}
    </div>
@endif 