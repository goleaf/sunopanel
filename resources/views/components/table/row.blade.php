@props(['zebra' => false, 'index' => 0])

<tr class="{{ $zebra && $index % 2 === 1 ? 'bg-gray-50' : 'bg-white' }}">
    {{ $slot }}
</tr> 