@props(['color' => 'primary', 'size' => 'md'])

@php
    $colorClasses = [
        'primary' => 'bg-primary/10 text-primary-600 border-primary-200',
        'secondary' => 'bg-secondary/10 text-secondary-600 border-secondary-200',
        'accent' => 'bg-accent/10 text-accent-600 border-accent-200',
        'info' => 'bg-info/10 text-info-600 border-info-200',
        'success' => 'bg-success/10 text-success-600 border-success-200',
        'warning' => 'bg-warning/10 text-warning-600 border-warning-200',
        'error' => 'bg-error/10 text-error-600 border-error-200',
        'neutral' => 'bg-neutral/10 text-neutral-600 border-neutral-200',
    ];

    $sizeClasses = [
        'sm' => 'text-xs px-1.5 py-0.5',
        'md' => 'text-sm px-2 py-1',
        'lg' => 'text-base px-2.5 py-1.5',
    ];

    $classes = $colorClasses[$color] . ' ' . $sizeClasses[$size] . ' rounded border inline-flex items-center justify-center';
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span> 