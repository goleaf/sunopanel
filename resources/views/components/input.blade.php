@props([
    'type' => 'text',
    'label' => null,
    'error' => null,
    'id' => null,
    'name' => null,
    'required' => false,
    'helpText' => '',
    'tooltip' => '',
    'tooltipPosition' => 'top',
])

@php
    $inputId = $id ?? $name ?? 'input-' . \Illuminate\Support\Str::random(6);
    $hasError = $error || ($name && $errors->has($name));
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
@endphp

<div>
    @if($label)
        @if($tooltip)
            <x-label-with-tooltip 
                :for="$inputId" 
                :value="$label" 
                :required="$required" 
                :tooltip="$tooltip"
                :tooltipPosition="$tooltipPosition"
                class="mb-1"
            />
        @else
            <x-label :for="$inputId" :value="$label" :required="$required" />
        @endif
    @endif

    <div class="relative rounded-md">
        <input 
            type="{{ $type }}" 
            id="{{ $inputId }}" 
            name="{{ $name }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge([
                'class' => 'input input-bordered w-full transition-colors duration-200' . 
                ($hasError ? ' input-error focus:input-error' : '')
            ]) }}
        >
        
        @if($hasError && $type !== 'password')
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="h-5 w-5 text-error" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
        @endif
    </div>

    @if($errorMessage)
        <div class="mt-1 text-sm text-error">{{ $errorMessage }}</div>
    @elseif($name && $errors->has($name))
        <x-input-error :for="$name" />
    @endif

    @if($helpText)
        <p class="mt-1 text-sm text-gray-500">{{ $helpText }}</p>
    @endif
</div> 