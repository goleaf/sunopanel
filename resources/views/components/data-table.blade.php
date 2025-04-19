@props([
    'headers' => [],
    'data' => null, 
    'hasSearch' => false,
    'searchPlaceholder' => 'Search...',
    'searchRoute' => null,
    'sortColumn' => null,
    'sortDirection' => 'asc',
    'emptyMessage' => 'No data available',
    'striped' => true,
    'hover' => true,
    'bordered' => false,
    'compact' => false,
    'header' => null,
    'body' => null,
    'footer' => null,
])

@php
    $tableClasses = 'table w-full';
    
    // Add variant styles
    if ($striped) {
        $tableClasses .= ' table-zebra';
    }
    
    if ($hover) {
        $tableClasses .= ' table-hover';
    }
    
    if ($bordered) {
        $tableClasses .= ' table-bordered';
    }
    
    if ($compact) {
        $tableClasses .= ' table-xs';
    }
@endphp

<div class="overflow-x-auto">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-2 sm:space-y-0 py-4 px-6 bg-gray-50 border-b">
        @if($hasSearch)
            <form method="GET" action="{{ $searchRoute }}" class="w-full sm:w-auto flex">
                <div class="relative">
                    <input
                        type="text"
                        name="search"
                        placeholder="{{ $searchPlaceholder }}"
                        value="{{ request('search') }}"
                        class="block w-full sm:w-72 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <button type="submit" class="text-gray-400 hover:text-gray-600">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                @if(request()->has('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}" />
                @endif
                
                @if(request()->has('order'))
                    <input type="hidden" name="order" value="{{ request('order') }}" />
                @endif
                
                @if(request()->has('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}" />
                @endif
                
                @if(isset($filters) && $filters)
                    {{ $filters }}
                @endif
            </form>
        @elseif(isset($filters) && $filters)
            <form method="GET" action="{{ url()->current() }}" class="w-full">
                {{ $filters }}
                
                @if(request()->has('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}" />
                @endif
                
                @if(request()->has('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}" />
                @endif
                
                @if(request()->has('order'))
                    <input type="hidden" name="order" value="{{ request('order') }}" />
                @endif
            </form>
        @endif
        
        @if(isset($topRight))
            <div class="ml-auto">
                {{ $topRight }}
            </div>
        @endif
    </div>

    <table class="{{ $tableClasses }}">
        @if ($header)
            <thead>
                <tr class="bg-base-200">
                    {{ $header }}
                </tr>
            </thead>
        @endif
        
        <tbody>
            @if ($body)
                {{ $body }}
            @else
                @if($data && count($data) > 0)
                    {{ $slot }}
                @else
                    <tr>
                        <td colspan="{{ count($headers) }}" class="px-6 py-8 text-center text-gray-500">
                            {{ $emptyMessage }}
                        </td>
                    </tr>
                @endif
            @endif
        </tbody>
        
        @if ($footer)
            <tfoot>
                <tr class="bg-base-200">
                    {{ $footer }}
                </tr>
            </tfoot>
        @endif
    </table>
    
    @if(isset($pagination) && $pagination)
        <div class="px-6 py-3 bg-gray-50 border-t">
            {{ $pagination }}
        </div>
    @endif
</div> 