@props([
    'title' => '',
    'value' => '',
    'comparison' => null,
    'trend' => null, // up, down, flat
    'percentage' => null,
    'icon' => null,
    'iconBg' => 'bg-primary/10',
    'iconColor' => 'text-primary',
    'detailsLink' => null,
    'loading' => false,
])

<div {{ $attributes->merge(['class' => 'card bg-base-100 shadow hover:shadow-md transition-shadow duration-300']) }}>
    <div class="card-body p-5">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="text-sm font-medium text-base-content/70">{{ $title }}</h3>
                
                @if($loading)
                    <div class="animate-pulse h-8 bg-base-300 rounded w-24 my-1"></div>
                @else
                    <div class="text-2xl lg:text-3xl font-bold tracking-tight">{{ $value }}</div>
                @endif
                
                @if($trend && $percentage)
                    <div class="mt-1 flex items-center text-sm">
                        @if($trend === 'up')
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-success mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-success">{{ $percentage }}%</span>
                        @elseif($trend === 'down')
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-error mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12 13a1 1 0 100 2h5a1 1 0 001-1v-5a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586l-4.293-4.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-error">{{ $percentage }}%</span>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-info mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M8 9a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V5z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-info">{{ $percentage }}%</span>
                        @endif
                        
                        @if($comparison)
                            <span class="text-base-content/60 ml-1">{{ $comparison }}</span>
                        @endif
                    </div>
                @endif
            </div>
            
            @if($icon)
                <div class="p-3 rounded-full {{ $iconBg }} {{ $iconColor }}">
                    {!! $icon !!}
                </div>
            @endif
        </div>
        
        @if($detailsLink)
            <div class="card-actions justify-end mt-4">
                <a href="{{ $detailsLink }}" class="text-xs text-primary hover:underline">
                    View details →
                </a>
            </div>
        @endif
        
        {{ $slot }}
    </div>
</div> 