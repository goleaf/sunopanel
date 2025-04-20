@props([
    'for',
    'value' => null,
    'required' => false,
])

<label for="{{ $for }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
    {{ $slot->isEmpty() ? $value : $slot }}
    @if($required)
        <span class="text-red-500">*</span>
    @endif
</label> 