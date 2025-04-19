@props([
    'label' => null,
    'name' => '',
    'id' => null,
    'options' => [],
    'selected' => '',
    'placeholder' => '-- Select an option --',
    'showPlaceholder' => true,
    'helpText' => '',
    'error' => '',
    'required' => false,
    'disabled' => false,
    'tooltip' => '',
    'tooltipPosition' => 'top',
])

@php
    $id = $id ?? $name;
    $hasError = !empty($error) || ($name && $errors->has($name));
    $errorMessage = $error ?: ($name ? $errors->first($name) : '');
    $baseClasses = 'block w-full rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500';
    $classes = $baseClasses . ' ' . ($hasError
        ? 'border-red-300 text-red-900'
        : 'border-gray-300');
        
    if ($disabled) {
        $classes .= ' opacity-50 cursor-not-allowed bg-gray-100';
    }
@endphp

<div>
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-base-content mb-1">
            {{ $label }}
            @if($required) <span class="text-error">*</span> @endif
        </label>
    @endif
    
    <div class="relative">
        <select
            name="{{ $name }}"
            id="{{ $id }}"
            {{ $disabled ? 'disabled' : '' }}
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge(['class' => 'select select-bordered w-full' . 
                ($hasError ? ' select-error' : '')]) 
            }}
        >
            @if($showPlaceholder)
                <option value="" {{ $selected === '' ? 'selected' : '' }}>{{ $placeholder }}</option>
            @endif
            
            @foreach($options as $value => $label)
                <option value="{{ $value }}" {{ (string)$value === (string)$selected ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        
        @if($hasError)
            <div class="absolute inset-y-0 right-8 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-error" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
        @endif
    </div>
    
    @if($hasError)
        <div class="mt-1 text-sm text-error">{{ $errorMessage }}</div>
    @endif
    
    @if($helpText)
        <p class="mt-1 text-sm text-gray-500">{{ $helpText }}</p>
    @endif
</div> 