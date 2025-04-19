@props(['title'])

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <x-heading :level="3" class="font-semibold text-gray-800">
            {{ $title }}
        </x-heading>
    </div>
    
    <div class="divide-y divide-gray-200">
        {{ $slot }}
    </div>
</div> 