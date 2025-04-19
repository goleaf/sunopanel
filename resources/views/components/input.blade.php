@props([
    'type' => 'text',
    'label' => null,
    'error' => null,
    'id' => null,
    'name' => null,
])

@php
    $inputId = $id ?? $name ?? 'input-' . \Illuminate\Support\Str::random(6);
@endphp

<div>
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
        </label>
    @endif

    <div class="relative rounded-md shadow-sm">
        <input 
            type="{{ $type }}" 
            id="{{ $inputId }}" 
            name="{{ $name }}"
            {{ $attributes->merge([
                'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm' . 
                ($error ? ' border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500' : '')
            ]) }}
        >
    </div>

    @if($error)
        <p class="mt-2 text-sm text-red-600">{{ $error }}</p>
    @endif
</div> 