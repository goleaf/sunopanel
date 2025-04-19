@props([
    'action' => '#', 
    'method' => 'DELETE',
    'color' => 'base',
    'size' => 'sm',
    'confirmMessage' => null
])

@php
    // Base classes for this type of button
    $baseClasses = 'btn';
    
    // Color and size classes
    $colorClass = "btn-{$color}";
    $sizeClass = "btn-{$size}";
    
    // Combined classes
    $classes = "{$baseClasses} {$colorClass} {$sizeClass} w-full text-left justify-start";
@endphp

<form method="POST" action="{{ $action }}" class="inline-block">
    @csrf
    @method($method)
    
    <button 
        type="submit" 
        {{ $confirmMessage ? "onclick=\"return confirm('{$confirmMessage}')\"" : '' }}
        {{ $attributes->merge(['class' => $classes]) }}
    >
        {{ $slot }}
    </button>
</form> 