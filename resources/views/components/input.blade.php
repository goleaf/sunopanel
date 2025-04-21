@props([
    'id' => null,
    'name' => null,
    'type' => 'text',
    'placeholder' => null,
    'value' => null,
    'required' => false,
    'disabled' => false,
    'error' => false,
])

@php
    $inputId = $id ?? $name ?? null;
    $baseClasses = 'w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none';
    $stateClasses = $error 
        ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' 
        : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500';
    $disabledClasses = $disabled ? 'bg-gray-100 cursor-not-allowed' : '';
@endphp

        <input 
    @if($inputId) id="{{ $inputId }}" @endif
    @if($name) name="{{ $name }}" @endif
            type="{{ $type }}" 
    @if($placeholder) placeholder="{{ $placeholder }}" @endif
    @if($value !== null) value="{{ $value }}" @endif
    @if($required) required @endif
    @if($disabled) disabled @endif
    {{ $attributes->merge(['class' => "{$baseClasses} {$stateClasses} {$disabledClasses}"]) }}
/>

@if($error && is_string($error))
    <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif