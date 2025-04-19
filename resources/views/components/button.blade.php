@props([
    'type' => 'button',
    'variant' => 'primary', // primary, secondary, success, warning, error, ghost, link
    'size' => 'md', // xs, sm, md, lg
    'icon' => null,
    'iconPosition' => 'left',
    'disabled' => false,
    'outline' => false,
    'square' => false,
    'loading' => false,
    'fullWidth' => false,
])

@php
    $base = 'btn transition-all duration-200 ease-in-out focus:outline-none';
    
    $variants = [
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary',
        'success' => 'btn-success',
        'warning' => 'btn-warning',
        'error' => 'btn-error',
        'ghost' => 'btn-ghost',
        'link' => 'btn-link',
        'info' => 'btn-info',
        'accent' => 'btn-accent',
        'neutral' => 'btn-neutral',
        'base' => 'hover:bg-base-200',
    ];
    
    $sizes = [
        'xs' => 'btn-xs',
        'sm' => 'btn-sm',
        'md' => 'btn-md',
        'lg' => 'btn-lg',
    ];
    
    $classes = implode(' ', [
        $base,
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
        $outline ? 'btn-outline' : '',
        $square ? 'btn-square' : '',
        $disabled || $loading ? 'btn-disabled' : '',
        $fullWidth ? 'w-full' : '',
    ]);
@endphp

<button 
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $classes]) }}
    @disabled($disabled || $loading)
>
    @if($loading)
        <span class="loading loading-spinner"></span>
    @endif
    
    @if($icon && $iconPosition === 'left' && !$loading)
        <span class="mr-2">{!! $icon !!}</span>
    @endif

    <span class="{{ $square ? 'sr-only' : '' }}">
        {{ $slot }}
    </span>

    @if($icon && $iconPosition === 'right' && !$loading)
        <span class="ml-2">{!! $icon !!}</span>
    @endif
</button>