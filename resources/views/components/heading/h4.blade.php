@props(['class' => ''])

<h4 {{ $attributes->merge(['class' => 'text-base sm:text-lg font-medium text-gray-800 ' . $class]) }}>
    {{ $slot }}
</h4> 