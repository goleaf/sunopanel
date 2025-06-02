@php
    $brand = config('navigation.brand');
@endphp

<div class="flex items-center space-x-4">
    <a href="{{ route($brand['route']) }}" class="flex items-center space-x-3 group">
        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center group-hover:scale-105 transition-transform duration-200">
            <x-icon name="{{ $brand['icon'] }}" class="w-5 h-5 text-white" />
        </div>
        <span class="text-xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
            {{ $brand['name'] }}
        </span>
    </a>
</div> 