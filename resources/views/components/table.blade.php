@props([
    'headers' => [],
    'headerClasses' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider',
    'bodyClasses' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-500',
    'sortField' => null,
    'sortDirection' => 'asc'
])

<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-gray-200']) }}>
        <thead class="bg-gray-50">
            <tr>
                @foreach($headers as $key => $header)
                    <th scope="col" class="{{ $headerClasses }}">
                        @if(is_array($header) && isset($header['field']) && $sortField !== null)
                            <x-sort-link 
                                :sortField="$sortField" 
                                :field="$header['field']" 
                                :direction="$sortDirection"
                                :label="$header['label'] ?? $header['field']"
                            />
                        @elseif(is_string($header))
                            {{ $header }}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            {{ $slot }}
        </tbody>
    </table>
</div> 