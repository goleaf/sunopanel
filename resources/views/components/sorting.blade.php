@props([
    'columns' => [],
    'sortField' => null,
    'sortDirection' => 'asc'
])

<div class="flex items-center space-x-4">
    <span class="text-sm font-medium text-gray-700">Sort by:</span>
    <div class="relative">
        <select
            onchange="window.location = this.value"
            class="block pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
        >
            @foreach($columns as $column)
                <option 
                    value="{{ $column['url'] ?? '#' }}"
                    {{ ($sortField === $column['field']) ? 'selected' : '' }}
                >
                    {{ $column['label'] }}
                    
                    @if($sortField === $column['field'])
                        ({{ $sortDirection === 'asc' ? 'A-Z' : 'Z-A' }})
                    @endif
                </option>
            @endforeach
        </select>
    </div>
</div> 