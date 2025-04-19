@props([
    'action' => '#', 
    'method' => 'DELETE',
    'color' => 'primary',
    'size' => 'sm',
    'confirmMessage' => null,
    'disabled' => false,
    'outline' => false,
    'fullWidth' => true,
    'icon' => false
])

@php
    // Base classes for this type of button
    $baseClasses = 'btn inline-flex items-center transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';
    
    // Color variants mapping - same as in button component
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
        'base' => 'hover:bg-base-200', // For backward compatibility
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
    $justifyClass = $fullWidth ? 'justify-start' : 'justify-center';
    
    // Get the appropriate color class, defaulting to primary if not found
    $colorClass = $colorClasses[$color] ?? $colorClasses['primary'];
    
    // Get the appropriate size class, defaulting to medium if not found
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['sm'];
    
    // Combined classes
    $classes = "{$baseClasses} {$colorClass} {$sizeClass} {$outlineClass} {$iconClass} {$disabledClass} {$fullWidthClass} {$justifyClass}";
@endphp

<form method="POST" action="{{ $action }}" class="inline-block w-full">
    @csrf
    @method($method)
    
    <button 
        type="submit" 
        {{ $disabled ? 'disabled' : '' }}
        {{ $confirmMessage ? "onclick=\"return confirm('{$confirmMessage}')\"" : '' }}
        {{ $attributes->merge(['class' => $classes]) }}
    >
        {{ $slot }}
    </button>
</form> 