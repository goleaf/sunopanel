@props(['class' => ''])

<h2 {{ $attributes->merge(['class' => 'text-xl sm:text-2xl font-semibold text-gray-800 ' . $class]) }}>
    {{ $slot }}
</h2> 