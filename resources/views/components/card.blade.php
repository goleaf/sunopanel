@props([
    'title' => null,
    'subtitle' => null,
    'footer' => null,
    'rounded' => 'md',
    'padding' => 'normal',
    'bodyClass' => '',
])

@php
    $classes = 'card bg-base-100 shadow-md';
    
    if ($rounded === 'none') {
        $classes .= '';
    } elseif ($rounded === 'sm') {
        $classes .= ' rounded-sm';
    } elseif ($rounded === 'lg') {
        $classes .= ' rounded-lg';
    } elseif ($rounded === 'xl') {
        $classes .= ' rounded-xl';
    } elseif ($rounded === 'full') {
        $classes .= ' rounded-full';
    } else {
        $classes .= ' rounded-md';
    }
    
    $bodyClasses = 'card-body';
    if ($padding === 'none') {
        $bodyClasses .= ' p-0';
    } elseif ($padding === 'sm') {
        $bodyClasses .= ' p-3';
    } elseif ($padding === 'lg') {
        $bodyClasses .= ' p-6';
    } elseif ($padding === 'xl') {
        $bodyClasses .= ' p-8';
    }
    
    if ($bodyClass) {
        $bodyClasses .= ' ' . $bodyClass;
    }
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if($title || $subtitle)
        <div class="card-title p-4 border-b border-base-200 flex items-center justify-between">
            <div>
                @if($title)
                    <h3 class="text-lg font-semibold">{{ $title }}</h3>
                @endif
                
                @if($subtitle)
                    <p class="text-sm text-base-content/70">{{ $subtitle }}</p>
                @endif
            </div>
            
            @if(isset($titleActions))
                <div class="flex items-center space-x-2">
                    {{ $titleActions }}
                </div>
            @endif
        </div>
    @endif
    
    <div class="{{ $bodyClasses }}">
        {{ $slot }}
    </div>
    
    @if($footer)
        <div class="card-footer p-4 border-t border-base-200">
            {{ $footer }}
        </div>
    @endif
</div> 