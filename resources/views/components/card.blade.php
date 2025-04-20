@props([
    'title' => null,
    'actions' => null,
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'bg-white shadow-md rounded-lg overflow-hidden']) }}>
    @if($title || $actions)
        <div class="flex flex-col sm:flex-row justify-between items-center px-6 py-4 border-b border-gray-200">
            @if($title)
                <h2 class="text-xl font-semibold text-gray-900">{{ $title }}</h2>
            @endif
            
            @if($actions)
                <div class="mt-4 sm:mt-0">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif
    
    <div @class(['px-6 py-4' => $padding])>
        {{ $slot }}
    </div>
</div> 