@props([
    'route' => '#',
    'placeholder' => 'Search...',
    'method' => 'GET',
    'value' => '',
    'filters' => [],
    'activeFilters' => [],
    'advancedMode' => false,
    'submitOnChange' => false,
])

<div
    x-data="{
        advancedMode: {{ $advancedMode ? 'true' : 'false' }},
        query: '{{ $value }}',
        filters: {},
        init() {
            // Initialize filters with their current values
            @foreach($activeFilters as $name => $value)
                this.filters['{{ $name }}'] = '{{ $value }}';
            @endforeach
        }
    }"
    class="w-full"
>
    <form
        action="{{ $route }}"
        method="{{ $method }}"
        @if($submitOnChange) 
            x-on:change="$el.submit()" 
        @endif
        class="space-y-4"
    >
        <div class="relative flex w-full">
            <div class="relative flex-grow">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input
                    type="search"
                    name="q"
                    x-model="query"
                    placeholder="{{ $placeholder }}"
                    autocomplete="off"
                    class="block w-full p-2.5 pl-10 pr-20 text-sm border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                />
                <button 
                    type="button"
                    @click="advancedMode = !advancedMode"
                    class="absolute inset-y-0 right-12 flex items-center pr-3"
                >
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" :class="{'text-blue-500 dark:text-blue-300': advancedMode}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                </button>
            </div>
            <button 
                type="submit"
                class="inline-flex items-center py-2.5 px-4 ml-2 text-sm font-medium text-white bg-blue-600 rounded-lg border border-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-blue-700"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Search
            </button>
        </div>

        <!-- Advanced Search Filters -->
        <div x-show="advancedMode" x-transition class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Advanced Filters</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($filters as $name => $filter)
                    <div class="space-y-1">
                        <label for="filter_{{ $name }}" class="text-xs font-medium text-gray-700 dark:text-gray-300">
                            {{ $filter['label'] ?? ucfirst($name) }}
                        </label>
                        
                        @if($filter['type'] == 'select')
                            <select 
                                name="filters[{{ $name }}]" 
                                id="filter_{{ $name }}"
                                x-model="filters.{{ $name }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            >
                                <option value="">{{ $filter['placeholder'] ?? 'Select...' }}</option>
                                @foreach($filter['options'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        @elseif($filter['type'] == 'date')
                            <input 
                                type="date" 
                                name="filters[{{ $name }}]" 
                                id="filter_{{ $name }}"
                                x-model="filters.{{ $name }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            >
                        @elseif($filter['type'] == 'range')
                            <div class="flex items-center space-x-2">
                                <input 
                                    type="number" 
                                    name="filters[{{ $name }}_min]" 
                                    id="filter_{{ $name }}_min"
                                    x-model="filters.{{ $name }}_min"
                                    placeholder="Min"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                >
                                <span class="text-gray-500">-</span>
                                <input 
                                    type="number" 
                                    name="filters[{{ $name }}_max]" 
                                    id="filter_{{ $name }}_max"
                                    x-model="filters.{{ $name }}_max"
                                    placeholder="Max"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                >
                            </div>
                        @else
                            <input 
                                type="text" 
                                name="filters[{{ $name }}]" 
                                id="filter_{{ $name }}"
                                x-model="filters.{{ $name }}"
                                placeholder="{{ $filter['placeholder'] ?? '' }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            >
                        @endif
                    </div>
                @endforeach
            </div>
            
            <div class="flex justify-end mt-4 space-x-2">
                <button 
                    type="reset" 
                    @click="
                        filters = {};
                        query = '';
                        advancedMode = false;
                    "
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white rounded-lg border border-gray-300 hover:bg-gray-100 focus:ring-2 focus:ring-blue-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-blue-700"
                >
                    Clear All
                </button>
                
                <button 
                    type="submit"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg border border-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-blue-700"
                >
                    Apply Filters
                </button>
            </div>
        </div>
        
        <!-- Active Filters -->
        <div x-show="Object.values(filters).some(value => value !== '')" class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Active filters:</span>
            
            <template x-for="(value, name) in filters" :key="name">
                <button 
                    x-show="value !== ''"
                    type="button"
                    @click="filters[name] = ''; $el.closest('form').submit()"
                    class="inline-flex items-center bg-blue-100 text-blue-800 text-xs font-medium rounded px-2 py-0.5 dark:bg-blue-900 dark:text-blue-300"
                >
                    <span x-text="name.replace('_', ' ') + ': ' + value"></span>
                    <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </template>
            
            <button 
                type="button"
                @click="
                    filters = {};
                    $el.closest('form').submit();
                "
                class="text-xs text-blue-600 dark:text-blue-400 hover:underline ml-2"
            >
                Clear all filters
            </button>
        </div>
    </form>
</div> 