@props([
    'label' => null,
    'name' => '',
    'id' => null,
    'options' => [],
    'selected' => '',
    'placeholder' => '-- Select an option --',
    'showPlaceholder' => true,
    'helperText' => '',
    'error' => '',
    'required' => false,
    'disabled' => false,
])

@php
    $id = $id ?? $name;
    $hasError = !empty($error);
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
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    
    <select
        name="{{ $name }}"
        id="{{ $id }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => $classes]) }}
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
    
    @if($helperText)
        <p class="mt-1 text-sm text-gray-500">{{ $helperText }}</p>
    @endif
    
    @if($hasError)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif
</div> 