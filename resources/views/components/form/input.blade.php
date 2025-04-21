@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'type' => 'text',
    'icon' => null,
    'error' => null,
    'required' => false,
    'wrapperClass' => '',
    'inputClass' => '',
])

@php
    // If name is not provided but id is, use id as name
    $inputName = $name ?? $id ?? '';
    $inputId = $id ?? $name ?? '';
@endphp

<div class="{{ $wrapperClass }}">
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <div class="relative mt-1 rounded-md shadow-sm">
        @if($icon)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                {!! $icon !!}
            </div>
        @endif
        
        <input 
            type="{{ $type }}" 
            id="{{ $inputId }}"
            @if($inputName) name="{{ $inputName }}" @endif
            {{ $attributes->merge(['class' => 'input input-bordered w-full ' . ($icon ? 'pl-10' : '') . ' ' . $inputClass]) }}
            @required($required)
        >
    </div>
    
    @if($error)
        <x-form.error name="{{ $inputName }}" message="{{ $error }}" />
    @elseif($inputName && $errors->has($inputName))
        <div class="mt-1 text-xs text-red-600 dark:text-red-400">
            {{ $errors->first($inputName) }}
        </div>
    @endif
</div> 