@props(['header' => null])

<x-layouts.app :header="$header">
    {{ $slot }}
</x-layouts.app> 