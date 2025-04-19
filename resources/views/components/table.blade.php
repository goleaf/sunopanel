@props([
    'headers' => [],
    'zebra' => false,
    'compact' => false,
    'class' => ''
])

<div class="overflow-x-auto {{ $class }}">
    <table class="table {{ $zebra ? 'table-zebra' : '' }} {{ $compact ? 'table-compact' : '' }}">
        @if(!empty($headers))
            <thead>
                <tr>
                    @foreach($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div> 