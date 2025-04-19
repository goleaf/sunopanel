@props([
    'type' => 'button',
    'color' => 'primary',
    'size' => 'md',
    'href' => null,
    'disabled' => false,
    'outline' => false,
    'icon' => false,
])

@php
    $baseClasses = 'btn ';
    
    // Color variants
    if ($outline) {
        $baseClasses .= match($color) {
            'primary' => 'btn-outline btn-primary ',
            'secondary' => 'btn-outline btn-secondary ',
            'accent' => 'btn-outline btn-accent ',
            'info' => 'btn-outline btn-info ',
            'success' => 'btn-outline btn-success ',
            'warning' => 'btn-outline btn-warning ',
            'error' => 'btn-outline btn-error ',
            'ghost' => 'btn-ghost ',
            'link' => 'btn-link ',
            default => 'btn-outline ',
        };
    } else {
        $baseClasses .= match($color) {
            'primary' => 'btn-primary ',
            'secondary' => 'btn-secondary ',
            'accent' => 'btn-accent ',
            'info' => 'btn-info ',
            'success' => 'btn-success ',
            'warning' => 'btn-warning ',
            'error' => 'btn-error ',
            'ghost' => 'btn-ghost ',
            'link' => 'btn-link ',
            default => '',
        };
    }
    
    // Size variants
    $baseClasses .= match($size) {
        'xs' => 'btn-xs ',
        'sm' => 'btn-sm ',
        'lg' => 'btn-lg ',
        default => '',
    };
    
    // Special variants
    if ($icon) {
        $baseClasses .= 'btn-square ';
    }
    
    if ($disabled) {
        $baseClasses .= 'btn-disabled ';
    }
    
    $attributes = $attributes->class([$baseClasses])->merge([
        'type' => $href ? null : $type,
        'disabled' => $disabled,
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes }}>
        {{ $slot }}
    </button>
@endif 