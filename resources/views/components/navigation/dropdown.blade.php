@props(['dropdown'])

@php
    $isActive = false;
    foreach ($dropdown['active_routes'] as $route) {
        if (request()->routeIs($route)) {
            $isActive = true;
            break;
        }
    }
    
    $buttonClasses = 'nav-link group-hover:nav-link-active' . ($isActive ? ' nav-link-active' : '');
@endphp

<div class="relative group mr-6 pl-6 border-l border-gray-200">
    <button class="{{ $buttonClasses }}">
        <x-icon name="{{ $dropdown['icon'] }}" />
        {{ $dropdown['label'] }}
        <x-icon name="chevron-down" class="w-3 h-3 ml-1 transition-transform group-hover:rotate-180" />
    </button>
    
    <div class="absolute top-full left-0 mt-1 w-56 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
        <div class="py-2">
            @foreach ($dropdown['items'] as $item)
                @if (isset($item['separator']) && $item['separator'])
                    <div class="border-t border-gray-100 my-1"></div>
                @endif
                
                @php
                    $itemIsActive = false;
                    foreach ($item['active_routes'] as $route) {
                        if (request()->routeIs($route)) {
                            $itemIsActive = true;
                            break;
                        }
                    }
                    
                    $itemClasses = 'dropdown-link' . ($itemIsActive ? ' dropdown-link-active' : '');
                @endphp
                
                <a href="{{ route($item['route']) }}" class="{{ $itemClasses }}">
                    <x-icon name="{{ $item['icon'] }}" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</div> 