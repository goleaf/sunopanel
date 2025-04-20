@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'options' => [],
    'selected' => null,
    'error' => null,
    'required' => false,
    'searchable' => false,
    'wrapperClass' => '',
])

@php
    // If name is not provided but id is, use id as name
    $selectName = $name ?? $id ?? '';
    $selectId = $id ?? $name ?? '';
@endphp

<div class="{{ $wrapperClass }}" x-data="{ open: false, search: '' }">
    @if($label)
        <label for="{{ $selectId }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <div class="relative mt-1">
        <select 
            id="{{ $selectId }}"
            @if($selectName) name="{{ $selectName }}" @endif
            {{ $attributes->merge(['class' => 'select select-bordered w-full']) }}
            @required($required)
            x-model="selected"
            x-on:click="open = !open"
        >
            {{ $slot }}
        </select>
        
        @if($searchable)
            <div class="absolute inset-0 flex items-center px-3 pointer-events-none">
                <input 
                    type="text" 
                    class="input input-ghost w-full pl-8"
                    x-model="search"
                    placeholder="Search..."
                    x-on:click.stop
                >
            </div>
        @endif
    </div>
    
    @if($error)
        <x-form.error name="{{ $selectName }}" message="{{ $error }}" />
    @elseif($selectName && $errors->has($selectName))
        <div class="mt-1 text-xs text-red-600 dark:text-red-400">
            {{ $errors->first($selectName) }}
        </div>
    @endif
</div> 