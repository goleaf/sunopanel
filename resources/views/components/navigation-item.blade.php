@props([
    'title',
    'description',
    'icon',
    'route',
    'buttonText',
    'variant' => 'primary'
])

<div class="p-6 hover:bg-gray-50 transition-colors duration-200 border-b border-gray-100 last:border-b-0">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div @class([
                    'p-3 rounded-full flex items-center justify-center',
                    'bg-indigo-100 text-indigo-700' => $variant === 'primary' || $variant === 'indigo',
                    'bg-green-100 text-green-700' => $variant === 'success' || $variant === 'green',
                    'bg-red-100 text-red-700' => $variant === 'danger' || $variant === 'red',
                    'bg-yellow-100 text-yellow-700' => $variant === 'warning' || $variant === 'yellow',
                    'bg-blue-100 text-blue-700' => $variant === 'info' || $variant === 'blue',
                    'bg-purple-100 text-purple-700' => $variant === 'purple',
                    'bg-gray-100 text-gray-700' => $variant === 'secondary' || $variant === 'gray',
                ])>
                    <x-icon :name="$icon" class="h-6 w-6" />
                </div>
            </div>
            <div class="ml-4">
                <x-heading :level="4" class="mb-1">
                    {{ $title }}
                </x-heading>
                <p class="text-sm text-gray-600">{{ $description }}</p>
            </div>
        </div>
        <div class="mt-4 md:mt-0 md:ml-6 flex-shrink-0">
            <x-button href="{{ $route }}" :color="$variant" class="w-full md:w-auto justify-center md:justify-start">
                {{ $buttonText }}
            </x-button>
        </div>
    </div>
</div> 