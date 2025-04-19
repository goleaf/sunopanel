@props([
    'title' => 'Widget Title',
    'subtitle' => null,
    'type' => 'stats', // stats, chart, list
    'icon' => null,
    'variant' => 'primary', // primary, success, warning, danger, info
    'value' => null,
    'change' => null,
    'changeType' => 'increase', // increase, decrease
    'chartId' => null,
    'chartData' => null,
    'listItems' => null,
    'loading' => false,
    'link' => null,
    'linkText' => 'View More',
    'bodyClass' => '',
])

@php
    $variantClasses = [
        'primary' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
        'success' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
        'warning' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
        'danger' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
        'info' => 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-800',
    ];
    
    $iconColors = [
        'primary' => 'text-blue-500 dark:text-blue-400',
        'success' => 'text-green-500 dark:text-green-400',
        'warning' => 'text-yellow-500 dark:text-yellow-400',
        'danger' => 'text-red-500 dark:text-red-400',
        'info' => 'text-indigo-500 dark:text-indigo-400',
    ];
    
    $borderColors = [
        'primary' => 'border-blue-200 dark:border-blue-800',
        'success' => 'border-green-200 dark:border-green-800',
        'warning' => 'border-yellow-200 dark:border-yellow-800',
        'danger' => 'border-red-200 dark:border-red-800',
        'info' => 'border-indigo-200 dark:border-indigo-800',
    ];
    
    $changeClasses = [
        'increase' => 'text-green-600 dark:text-green-400',
        'decrease' => 'text-red-600 dark:text-red-400',
    ];
    
    $changeIcons = [
        'increase' => '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>',
        'decrease' => '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg shadow-sm border ' . $variantClasses[$variant]]) }}
    x-data="{ loading: {{ $loading ? 'true' : 'false' }} }"
>
    <!-- Widget Header -->
    <div class="p-4 flex items-center justify-between border-b {{ $borderColors[$variant] }}">
        <div class="flex items-center space-x-3">
            @if($icon)
                <div class="w-8 h-8 flex items-center justify-center rounded-full bg-white dark:bg-gray-800 {{ $iconColors[$variant] }}">
                    {!! $icon !!}
                </div>
            @endif
            <div>
                <h3 class="font-semibold text-gray-800 dark:text-white">{{ $title }}</h3>
                @if($subtitle)
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        
        <div>
            <template x-if="loading">
                <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </template>
        </div>
    </div>
    
    <!-- Widget Body -->
    <div class="p-4 {{ $bodyClass }}">
        @if($type === 'stats')
            <div class="flex flex-col">
                @if($value !== null)
                    <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ $value }}</div>
                @endif
                
                @if($change !== null)
                    <div class="flex items-center text-xs font-medium {{ $changeClasses[$changeType] }}">
                        {!! $changeIcons[$changeType] !!}
                        {{ $change }}
                    </div>
                @endif
            </div>
        @elseif($type === 'chart' && $chartId)
            <div class="h-48">
                <canvas id="{{ $chartId }}"></canvas>
            </div>
            @if($chartData)
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const ctx = document.getElementById('{{ $chartId }}').getContext('2d');
                        const chartData = @json($chartData);
                        
                        new Chart(ctx, chartData);
                    });
                </script>
            @endif
        @elseif($type === 'list' && $listItems)
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($listItems as $item)
                    <li class="py-2">
                        @if(isset($item['link']))
                            <a href="{{ $item['link'] }}" class="flex items-center hover:text-blue-600 dark:hover:text-blue-400">
                                @if(isset($item['icon']))
                                    <span class="mr-2">{!! $item['icon'] !!}</span>
                                @endif
                                <span>{{ $item['text'] }}</span>
                                @if(isset($item['badge']))
                                    <span class="ml-auto px-2 py-0.5 text-xs rounded-full {{ $item['badge']['class'] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $item['badge']['text'] }}
                                    </span>
                                @endif
                            </a>
                        @else
                            <div class="flex items-center">
                                @if(isset($item['icon']))
                                    <span class="mr-2">{!! $item['icon'] !!}</span>
                                @endif
                                <span>{{ $item['text'] }}</span>
                                @if(isset($item['badge']))
                                    <span class="ml-auto px-2 py-0.5 text-xs rounded-full {{ $item['badge']['class'] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $item['badge']['text'] }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            {{ $slot }}
        @endif
    </div>
    
    <!-- Widget Footer -->
    @if($link)
    <div class="px-4 py-3 border-t {{ $borderColors[$variant] }}">
        <a href="{{ $link }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
            {{ $linkText }}
            <svg class="inline-block w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
            </svg>
        </a>
    </div>
    @endif
</div> 