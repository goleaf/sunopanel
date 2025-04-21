@props([
    'type' => 'button',
    'href' => null,
    'color' => 'primary',
    'size' => 'md',
    'icon' => false,
    'iconPosition' => 'left',
    'disabled' => false,
    'fullWidth' => false,
    'withIcon' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-md focus:outline-none transition-colors duration-150 ease-in-out';
    
    $sizeClasses = [
        'xs' => 'px-2 py-1 text-xs',
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
        'xl' => 'px-6 py-3 text-lg',
    ][$size] ?? 'px-4 py-2 text-sm';
    
    $colorClasses = match($color) {
        'primary' => 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 border border-transparent',
        'secondary' => 'bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 border border-transparent',
        'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-2 focus:ring-offset-2 focus:ring-green-500 border border-transparent',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-2 focus:ring-offset-2 focus:ring-red-500 border border-transparent',
        'warning' => 'bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 border border-transparent',
        'outline' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500',
        'ghost' => 'text-gray-700 hover:bg-gray-100 focus:ring-2 focus:ring-gray-200 border border-transparent',
        'link' => 'text-indigo-600 hover:text-indigo-900 underline p-0 border-none',
        default => 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 border border-transparent',
    };
    
    $iconClasses = $icon ? 'p-2' : null;
    $disabledClasses = $disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : '';
    $fullWidthClasses = $fullWidth ? 'w-full' : '';
    
    $classes = trim("{$baseClasses} {$sizeClasses} {$colorClasses} {$iconClasses} {$disabledClasses} {$fullWidthClasses}");
@endphp

@if ($href)
    <a href="{{ $disabled ? '#' : $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($withIcon && $iconPosition === 'left')
            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
        @endif
        {{ $slot }}
        @if($withIcon && $iconPosition === 'right')
            <svg class="w-5 h-5 ml-2 -mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
    @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} @if($disabled) disabled @endif>
        @if($withIcon && $iconPosition === 'left')
            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
    @endif
        {{ $slot }}
        @if($withIcon && $iconPosition === 'right')
            <svg class="w-5 h-5 ml-2 -mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
    @endif
</button>
@endif