@props([
    'name' => null,
    'message' => null,
])

<p class="mt-1 text-xs text-red-600 dark:text-red-400" @if($name) id="{{ $name }}-error" @endif>
    {{ $slot->isEmpty() ? $message : $slot }}
</p> 