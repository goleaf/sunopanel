@props([
    'title' => 'Recent Activity',
    'activities' => [],
    'maxItems' => 5,
    'loading' => false,
    'emptyMessage' => 'No recent activities',
    'viewAllLink' => null,
])

<div {{ $attributes->merge(['class' => 'card bg-base-100 shadow hover:shadow-md transition-shadow duration-300']) }}>
    <div class="card-body p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="card-title text-lg font-medium">{{ $title }}</h3>
            @if($viewAllLink)
                <a href="{{ $viewAllLink }}" class="text-xs text-primary hover:underline">View all</a>
            @endif
        </div>

        @if($loading)
            <div class="space-y-3">
                @for($i = 0; $i < $maxItems; $i++)
                    <div class="flex items-center space-x-4">
                        <div class="animate-pulse h-10 w-10 rounded-full bg-base-300"></div>
                        <div class="flex-1">
                            <div class="animate-pulse h-4 bg-base-300 rounded w-3/4 mb-2"></div>
                            <div class="animate-pulse h-3 bg-base-300 rounded w-1/2"></div>
                        </div>
                    </div>
                @endfor
            </div>
        @elseif(count($activities) > 0)
            <div class="space-y-4">
                @foreach(array_slice($activities, 0, $maxItems) as $activity)
                    <div class="flex items-start space-x-3">
                        @if(isset($activity['icon']))
                            <div class="bg-base-200 p-2 rounded-full">
                                {!! $activity['icon'] !!}
                            </div>
                        @elseif(isset($activity['avatar']))
                            <div class="avatar">
                                <div class="w-10 rounded-full">
                                    <img src="{{ $activity['avatar'] }}" alt="Avatar">
                                </div>
                            </div>
                        @else
                            <div class="bg-primary/10 text-primary p-2 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        @endif
                        
                        <div class="flex-1">
                            <p class="text-sm font-medium">
                                {!! $activity['description'] ?? '' !!}
                            </p>
                            @if(isset($activity['time']))
                                <p class="text-xs text-base-content/60">{{ $activity['time'] }}</p>
                            @endif
                            @if(isset($activity['details']))
                                <p class="text-xs mt-1 text-base-content/70">{{ $activity['details'] }}</p>
                            @endif
                        </div>
                        
                        @if(isset($activity['action']))
                            <div>
                                <a href="{{ $activity['action']['url'] ?? '#' }}" class="btn btn-xs btn-ghost">
                                    {{ $activity['action']['label'] ?? 'View' }}
                                </a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-base-content/50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p>{{ $emptyMessage }}</p>
            </div>
        @endif

        {{ $slot }}
    </div>
</div> 