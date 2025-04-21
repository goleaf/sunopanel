@props(['sortField', 'field', 'direction', 'label' => null])

@php
    $label = $label ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $field));
    $isCurrent = $sortField === $field;
    $newDirection = $isCurrent && $direction === 'asc' ? 'desc' : 'asc';
    $params = array_merge(request()->except(['sort', 'direction', 'page']), [
        'sort' => $field,
        'direction' => $newDirection
    ]);
@endphp

<a href="{{ url()->current() . '?' . http_build_query($params) }}" class="group inline-flex items-center">
    <span>{{ $label }}</span>
    
    <span class="ml-2 flex-none rounded text-gray-400 group-hover:visible group-focus:visible">
        @if($isCurrent)
            @if($direction === 'asc')
                <svg class="h-4 w-4 text-indigo-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 5a.75.75 0 01.75.75v6.638l1.96-2.158a.75.75 0 111.08 1.04l-3.25 3.5a.75.75 0 01-1.08 0l-3.25-3.5a.75.75 0 111.08-1.04l1.96 2.158V5.75A.75.75 0 0110 5z" clip-rule="evenodd" />
                </svg>
            @else
                <svg class="h-4 w-4 text-indigo-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 15a.75.75 0 01-.75-.75V7.612L7.29 9.77a.75.75 0 01-1.08-1.04l3.25-3.5a.75.75 0 011.08 0l3.25 3.5a.75.75 0 11-1.08 1.04l-1.96-2.158v6.638A.75.75 0 0110 15z" clip-rule="evenodd" />
                </svg>
            @endif
        @else
            <svg class="h-4 w-4 opacity-0 group-hover:opacity-100" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
            </svg>
        @endif
    </span>
</a> 