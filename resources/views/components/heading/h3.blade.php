@props(['class' => ''])

<h3 {{ $attributes->merge(['class' => 'text-lg sm:text-xl font-semibold text-gray-800 ' . $class]) }}>
    {{ $slot }}
</h3> 