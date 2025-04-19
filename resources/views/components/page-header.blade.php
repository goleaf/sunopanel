@props(['title'])

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <x-heading :level="1" class="mb-2 md:mb-0">
            {{ $title }}
        </x-heading>
        
        @if (isset($buttons))
            <div class="flex space-x-2">
                {{ $buttons }}
            </div>
        @endif
    </div>
    
    @if (isset($description))
        <div class="mt-2 text-gray-600">
            {{ $description }}
        </div>
    @endif
</div> 