@props([
    'route' => null,
    'placeholder' => 'Search...',
    'value' => request('search', '')
])

<div class="flex">
    <form action="{{ $route ?? url()->current() }}" method="GET" class="relative flex-1">
        @if(request()->has('sort'))
            <input type="hidden" name="sort" value="{{ request('sort') }}">
        @endif
        
        @if(request()->has('direction'))
            <input type="hidden" name="direction" value="{{ request('direction') }}">
        @endif
        
        <div class="relative rounded-md shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
            </div>
            <input 
                type="text" 
                name="search" 
                id="search" 
                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 pr-12 py-2 sm:text-sm border-gray-300 rounded-md" 
                placeholder="{{ $placeholder }}"
                value="{{ $value }}"
                {{ $attributes }}
            >
            @if($value)
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <a href="{{ url()->current() }}" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            @endif
        </div>
        <button type="submit" class="hidden">Search</button>
    </form>
</div> 