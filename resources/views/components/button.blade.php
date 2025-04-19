@props([
    'type' => 'button',
    'color' => 'primary',
    'size' => 'md',
    'href' => null,
    'disabled' => false,
    'outline' => false,
    'icon' => false,
    'full' => false,
    'rounded' => false
])

@php
    // Base classes for all buttons
    $baseClasses = 'inline-flex items-center justify-center transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 font-medium';

    // Color variations (using DaisyUI classes)
    $colorClasses = [
        'primary' => $outline ? 'btn-outline btn-primary' : 'btn-primary',
        'secondary' => $outline ? 'btn-outline btn-secondary' : 'btn-secondary',
        'accent' => $outline ? 'btn-outline btn-accent' : 'btn-accent',
        'info' => $outline ? 'btn-outline btn-info' : 'btn-info',
        'success' => $outline ? 'btn-outline btn-success' : 'btn-success',
        'warning' => $outline ? 'btn-outline btn-warning' : 'btn-warning',
        'error' => $outline ? 'btn-outline btn-error' : 'btn-error',
        'ghost' => 'btn-ghost',
        'link' => 'btn-link',
        'base' => $outline ? 'btn-outline' : 'btn',
    ];

    // Size variations
    $sizeClasses = [
        'xs' => 'btn-xs',
        'sm' => 'btn-sm',
        'md' => 'btn-md',
        'lg' => 'btn-lg',
    ];

    // Special states
    $iconClasses = $icon ? 'btn-square' : '';
    $disabledClasses = $disabled ? 'btn-disabled opacity-60 cursor-not-allowed' : '';
    $fullClasses = $full ? 'w-full' : '';
    $roundedClasses = $rounded ? 'rounded-full' : '';

    // Combine all classes
    $classes = trim("btn {$colorClasses[$color]} {$sizeClasses[$size]} {$iconClasses} {$disabledClasses} {$fullClasses} {$roundedClasses}");
@endphp

@if ($href && !$disabled)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button 
        type="{{ $type }}" 
        {{ $disabled ? 'disabled' : '' }} 
        {{ $attributes->merge(['class' => $classes]) }}
    >
        {{ $slot }}
    </button>
@endif