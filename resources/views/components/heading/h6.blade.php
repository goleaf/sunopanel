@props(['class' => ''])

<h6 {{ $attributes->merge(['class' => 'text-xs sm:text-sm font-medium text-gray-700 ' . $class]) }}>
    {{ $slot }}
</h6> 