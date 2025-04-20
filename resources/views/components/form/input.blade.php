@props([
    'name',
    'label' => null,
    'type' => 'text',
    'icon' => null,
    'error' => null,
    'required' => false,
    'wrapperClass' => '',
    'inputClass' => '',
])

<div class="{{ $wrapperClass }}">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
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
            id="{{ $name }}"
            name="{{ $name }}"
            {{ $attributes->merge(['class' => 'input input-bordered w-full ' . ($icon ? 'pl-10' : '') . ' ' . $inputClass]) }}
            @required($required)
        >
    </div>
    
    @if($error)
        <x-form.error :name="$name" :message="$error" />
    @endif
</div> 