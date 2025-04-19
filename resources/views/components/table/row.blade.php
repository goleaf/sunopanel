@props(['active' => false, 'hover' => true])

<tr class="{{ $active ? 'active' : '' }} {{ $hover ? 'hover' : '' }}">
    {{ $slot }}
</tr> 