@props([
    'title',
    'actions' => null,
    'description' => null,
])

<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">
            {{ $title }}
        </h1>
        
        @if($actions)
            <div class="mt-4 sm:mt-0 flex space-x-3">
                {{ $actions }}
            </div>
        @endif
    </div>
    
    @if($description)
        <p class="mt-2 text-sm text-gray-600">
            {{ $description }}
        </p>
    @endif
</div> 