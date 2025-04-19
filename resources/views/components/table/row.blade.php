@props(['highlight' => false])

<tr {{ $attributes->merge(['class' => $highlight ? 'bg-base-200/30' : 'hover:bg-base-200/30']) }}>
    {{ $slot }}
</tr> 