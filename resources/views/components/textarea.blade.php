@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'error' => null,
    'required' => false,
    'helpText' => '',
    'rows' => 4,
])

@php
    $inputId = $id ?? $name ?? 'textarea-' . \Illuminate\Support\Str::random(6);
    $hasError = $error || ($name && $errors->has($name));
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
@endphp

<div>
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-base-content mb-1">
            {{ $label }}
            @if($required) <span class="text-error">*</span> @endif
        </label>
    @endif

    <div class="relative rounded-md">
        <textarea 
            id="{{ $inputId }}" 
            name="{{ $name }}"
            rows="{{ $rows }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge([
                'class' => 'textarea textarea-bordered w-full transition-colors duration-200' . 
                ($hasError ? ' textarea-error focus:textarea-error' : '')
            ]) }}
        >{{ $slot }}</textarea>
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