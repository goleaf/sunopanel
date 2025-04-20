@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'error' => null,
    'required' => false,
    'searchable' => false,
    'wrapperClass' => '',
])

<div class="{{ $wrapperClass }}" x-data="{ open: false, search: '' }">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <div class="relative mt-1">
        <select 
            id="{{ $name }}"
            name="{{ $name }}"
            {{ $attributes->merge(['class' => 'select select-bordered w-full']) }}
            @required($required)
            x-model="selected"
            x-on:click="open = !open"
        >
            @foreach($options as $value => $option)
                <option value="{{ $value }}" @selected($value == $selected)>
                    {{ $option }}
                </option>
            @endforeach
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
        <x-form.error :name="$name" :message="$error" />
    @endif
</div> 