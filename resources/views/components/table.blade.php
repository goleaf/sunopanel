@props([
    'headers' => [],
    'headerClasses' => 'px-6 py-3 text-left text-xs font-medium text-base-content opacity-70 uppercase tracking-wider',
    'bodyClasses' => 'px-6 py-4 whitespace-nowrap text-sm text-base-content',
    'sortField' => null,
    'sortDirection' => 'asc'
])

<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-base-300']) }}>
        <thead class="bg-base-200">
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
        <tbody class="bg-base-100 divide-y divide-base-300">
            {{ $slot }}
        </tbody>
    </table>
</div> 