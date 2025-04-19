@props([
    'type' => 'info', // info, success, warning, error
    'title' => '',
    'message' => '',
    'dismissible' => true,
    'icon' => true,
    'rounded' => true,
    'bordered' => false
])

@php
    $types = [
        'info' => [
            'bg' => 'bg-info/10',
            'border' => 'border-info',
            'text' => 'text-info-content',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>'
        ],
        'success' => [
            'bg' => 'bg-success/10',
            'border' => 'border-success',
            'text' => 'text-success-content',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>'
        ],
        'warning' => [
            'bg' => 'bg-warning/10',
            'border' => 'border-warning',
            'text' => 'text-warning-content',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>'
        ],
        'error' => [
            'bg' => 'bg-error/10',
            'border' => 'border-error',
            'text' => 'text-error-content',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>'
        ]
    ];

    $classes = [
        $types[$type]['bg'],
        $types[$type]['text'],
        $rounded ? 'rounded-lg' : '',
        $bordered ? 'border '.$types[$type]['border'] : '',
    ];
@endphp

<div x-data="{ show: true }" x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" {{ $attributes->merge(['class' => implode(' ', $classes).' p-4 my-4']) }}>
    <div class="flex items-start">
        @if($icon)
            <div class="flex-shrink-0 mr-3">
                {!! $types[$type]['icon'] !!}
            </div>
        @endif
        
        <div class="flex-1 mr-2">
            @if($title)
                <h3 class="text-sm font-medium mb-1">{{ $title }}</h3>
            @endif
            
            <div class="text-sm">
                @if(is_string($message))
                    {{ $message }}
                @else
                    {{ $slot }}
                @endif
            </div>
        </div>
        
        @if($dismissible)
            <div class="flex-shrink-0">
                <button @click="show = false" type="button" class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-base-300">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        @endif
    </div>
</div> 