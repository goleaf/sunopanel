@props(['item', 'mobile' => false])

@php
    $isActive = false;
    foreach ($item['active_routes'] as $route) {
        if (request()->routeIs($route)) {
            $isActive = true;
            break;
        }
    }
    
    $baseClasses = $mobile 
        ? 'mobile-nav-link' 
        : 'nav-link';
    
    $activeClasses = $mobile 
        ? 'mobile-nav-link-active' 
        : 'nav-link-active';
    
    $classes = $baseClasses . ($isActive ? ' ' . $activeClasses : '');
    
    $label = $mobile && isset($item['mobile_label']) ? $item['mobile_label'] : $item['label'];
@endphp

<a href="{{ route($item['route']) }}" class="{{ $classes }}">
    <x-icon name="{{ $item['icon'] }}" />
    {{ $label }}
</a> 