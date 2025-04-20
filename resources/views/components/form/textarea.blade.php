@props([
    'name',
    'label' => null,
    'error' => null,
    'required' => false,
    'rows' => 4,
    'wrapperClass' => '',
    'textareaClass' => '',
    'placeholder' => '',
    'helpText' => '',
])

<div class="{{ $wrapperClass }}">
    @if($label)
        <x-form.label :for="$name" :value="$label" :required="$required" />
    @endif
    
    <div class="relative mt-1 rounded-md shadow-sm">
        <textarea 
            id="{{ $name }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->merge(['class' => 'textarea textarea-bordered w-full ' . $textareaClass]) }}
            @required($required)
        >{{ $slot }}</textarea>
    </div>
    
    @if($error)
        <x-form.error :name="$name" :message="$error" />
    @elseif($name && $errors->has($name))
        <div class="mt-1 text-xs text-red-600 dark:text-red-400">
            {{ $errors->first($name) }}
        </div>
    @endif

    @if($helpText)
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $helpText }}</p>
    @endif
</div> 