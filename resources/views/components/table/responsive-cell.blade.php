@props(['label' => ''])

<td data-label="{{ $label }}" {{ $attributes }}>
    {{ $slot }}
</td> 