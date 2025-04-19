@props([
    'name',
    'message',
])

<p class="mt-1 text-xs text-red-600 dark:text-red-400" id="{{ $name }}-error">
    {{ $message }}
</p> 