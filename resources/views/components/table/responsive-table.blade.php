@props(['columns', 'striped' => true, 'compact' => false])

<div class="table-responsive-wrapper">
    <table {{ $attributes->merge(['class' => 'table w-full'.($striped ? ' table-zebra' : '')]) }}>
        <thead>
            <tr>
                @foreach($columns as $key => $column)
                    <th class="text-left">{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="{{ $compact ? 'table-compact-mobile' : '' }}">
            {{ $slot }}
        </tbody>
    </table>
</div> 