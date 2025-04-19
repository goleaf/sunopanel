@props(['href' => '#', 'icon' => null, 'label' => '', 'color' => 'primary'])

@php
$colors = [
    'primary' => 'text-primary hover:text-primary-focus',
    'secondary' => 'text-secondary hover:text-secondary-focus',
    'accent' => 'text-accent hover:text-accent-focus',
    'neutral' => 'text-neutral hover:text-neutral-focus',
    'info' => 'text-info hover:text-info-focus',
    'success' => 'text-success hover:text-success-focus',
    'warning' => 'text-warning hover:text-warning-focus',
    'error' => 'text-error hover:text-error-focus',
];

$colorClass = $colors[$color] ?? $colors['primary'];
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-1 text-sm $colorClass transition-colors duration-150"]) }}>
    @if($icon)
        <span class="mr-1">
            {!! $icon !!}
        </span>
    @endif
    {{ $label }}
</a> 