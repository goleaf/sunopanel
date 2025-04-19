@props(['class' => ''])

<h5 {{ $attributes->merge(['class' => 'text-sm sm:text-base font-medium text-gray-700 ' . $class]) }}>
    {{ $slot }}
</h5> 