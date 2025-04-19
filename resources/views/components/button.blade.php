@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconPosition' => 'left',
    'disabled' => false,
    'fullWidth' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';
    
    $variantClasses = [
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
        'secondary' => 'bg-gray-200 hover:bg-gray-300 text-gray-800 focus:ring-gray-500',
        'success' => 'bg-green-600 hover:bg-green-700 text-white focus:ring-green-500',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500',
        'warning' => 'bg-yellow-500 hover:bg-yellow-600 text-white focus:ring-yellow-500',
        'info' => 'bg-sky-500 hover:bg-sky-600 text-white focus:ring-sky-500',
        'light' => 'bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-300 focus:ring-gray-300',
        'dark' => 'bg-gray-800 hover:bg-gray-900 text-white focus:ring-gray-700',
        'outline' => 'bg-transparent hover:bg-gray-100 text-gray-700 border border-gray-300 focus:ring-gray-300',
        'link' => 'bg-transparent text-blue-600 hover:text-blue-700 hover:underline p-0 focus:ring-0'
    ][$variant] ?? 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500';
    
    $sizeClasses = [
        'xs' => 'py-1 px-2 text-xs',
        'sm' => 'py-1.5 px-3 text-sm',
        'md' => 'py-2 px-4 text-sm',
        'lg' => 'py-2.5 px-5 text-base',
        'xl' => 'py-3 px-6 text-lg',
    ][$size] ?? 'py-2 px-4 text-sm';
    
    $disabledClasses = $disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : '';
    $widthClasses = $fullWidth ? 'w-full' : '';
    
    $classes = trim("{$baseClasses} {$variantClasses} {$sizeClasses} {$disabledClasses} {$widthClasses}");
@endphp

<button 
    type="{{ $type }}"
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if($icon && $iconPosition === 'left')
        <span class="mr-2 -ml-1 {{ $size === 'xs' || $size === 'sm' ? 'w-4 h-4' : 'w-5 h-5' }}">{!! $icon !!}</span>
    @endif
    
    {{ $slot }}
    
    @if($icon && $iconPosition === 'right')
        <span class="ml-2 -mr-1 {{ $size === 'xs' || $size === 'sm' ? 'w-4 h-4' : 'w-5 h-5' }}">{!! $icon !!}</span>
    @endif
</button>