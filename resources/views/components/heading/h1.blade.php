@props(['class' => ''])

<h1 {{ $attributes->merge(['class' => 'text-2xl sm:text-3xl font-bold text-gray-900 ' . $class]) }}>
    {{ $slot }}
</h1> 