@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'error' => null,
    'required' => false,
    'rows' => 4,
    'wrapperClass' => '',
    'textareaClass' => '',
    'placeholder' => '',
    'helpText' => '',
])

@php
    // If name is not provided but id is, use id as name
    $textareaName = $name ?? $id ?? '';
    $textareaId = $id ?? $name ?? '';
@endphp

<div class="{{ $wrapperClass }}">
    @if($label)
        <label for="{{ $textareaId }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <div class="relative mt-1 rounded-md shadow-sm">
        <textarea 
            id="{{ $textareaId }}"
            @if($textareaName) name="{{ $textareaName }}" @endif
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->merge(['class' => 'textarea textarea-bordered w-full ' . $textareaClass]) }}
            @required($required)
        >{{ $slot }}</textarea>
    </div>
    
    @if($error)
        <x-form.error name="{{ $textareaName }}" message="{{ $error }}" />
    @elseif($textareaName && $errors->has($textareaName))
        <div class="mt-1 text-xs text-red-600 dark:text-red-400">
            {{ $errors->first($textareaName) }}
        </div>
    @endif

    @if($helpText)
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $helpText }}</p>
    @endif
</div> 