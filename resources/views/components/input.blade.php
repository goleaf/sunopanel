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
        <label for="{{ $inputId }}" class="block text-sm font-medium text-base-content mb-1">
            {{ $label }}
        </label>
    @endif

    <div class="relative rounded-md">
        <input 
            type="{{ $type }}" 
            id="{{ $inputId }}" 
            name="{{ $name }}"
            {{ $attributes->merge([
                'class' => 'input input-bordered w-full' . 
                ($error ? ' input-error' : '')
            ]) }}
        >
    </div>

    @if($error)
        <p class="mt-2 text-sm text-error">{{ $error }}</p>
    @endif
</div> 