@props(['perPage' => request('perPage', 10)])

<div class="relative inline-flex items-center rounded-md shadow-sm">
    <label for="perPage" class="sr-only">Results per page</label>
    <div class="flex items-center">
        <span class="text-sm text-gray-600 mr-2">Results per page:</span>
        <div class="relative">
            <select 
                name="perPage" 
                id="perPage" 
                class="pr-8 pl-3 py-2 text-sm border-gray-300 bg-white rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer appearance-none"
                onchange="this.form.submit()"
            >
                @foreach([10, 25, 50, 100] as $value)
                    <option value="{{ $value }}" {{ $perPage == $value ? 'selected' : '' }}>
                        {{ $value }}
                    </option>
                @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
    </div>
</div> 