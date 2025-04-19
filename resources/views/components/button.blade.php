@props([
    'type' => 'button',
    'color' => 'primary',
    'size' => 'md',
    'href' => null,
    'disabled' => false,
    'outline' => false,
    'icon' => false,
    'fullWidth' => false,
    'loading' => false,
    'rounded' => false
])

@php
    // Base classes for all buttons
    $baseClasses = 'btn inline-flex items-center transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';
    
    // Color variants mapping
    $colorClasses = [
        'primary' => 'btn-primary focus:ring-primary/50',
        'secondary' => 'btn-secondary focus:ring-secondary/50',
        'accent' => 'btn-accent focus:ring-accent/50',
        'info' => 'btn-info focus:ring-info/50',
        'success' => 'btn-success focus:ring-success/50',
        'warning' => 'btn-warning focus:ring-warning/50',
        'error' => 'btn-error focus:ring-error/50',
        'ghost' => 'btn-ghost hover:bg-base-200',
        'link' => 'btn-link hover:underline',
        'gray' => 'btn-neutral focus:ring-neutral/50',
        'indigo' => 'btn-primary focus:ring-primary/50', // Map indigo to primary for consistency
        'yellow' => 'btn-warning focus:ring-warning/50', // Map yellow to warning for consistency
        'red' => 'btn-error focus:ring-error/50', // Map red to error for consistency
    ];
    
    // Size variants
    $sizeClasses = [
        'xs' => 'btn-xs',
        'sm' => 'btn-sm',
        'md' => 'btn-md',
        'lg' => 'btn-lg',
    ];
    
    // Special states
    $outlineClass = $outline ? 'btn-outline' : '';
    $iconClass = $icon ? 'btn-square' : '';
    $disabledClass = $disabled ? 'btn-disabled opacity-60 cursor-not-allowed' : '';
    $fullWidthClass = $fullWidth ? 'w-full' : '';
    $loadingClass = $loading ? 'loading' : '';
    $roundedClass = $rounded ? 'rounded-full' : '';
    
    // Get the appropriate color class, defaulting to primary if not found
    $colorClass = $colorClasses[$color] ?? $colorClasses['primary'];
    
    // Get the appropriate size class, defaulting to medium if not found
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    
    // Combined classes
    $classes = "{$baseClasses} {$colorClass} {$sizeClass} {$outlineClass} {$iconClass} {$disabledClass} {$fullWidthClass} {$loadingClass} {$roundedClass}";
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