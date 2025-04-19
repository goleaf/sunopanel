@props([
    'header', 
    'body', 
    'striped' => false, 
    'hoverable' => true, 
    'compact' => false,
    'responsive' => true
])

<div class="overflow-x-auto bg-base-100 rounded-lg shadow">
    <table class="table w-full
        {{ $striped ? 'table-zebra' : '' }}
        {{ $hoverable ? 'table-hover' : '' }}
        {{ $compact ? 'table-sm' : '' }}
    ">
        <thead>
            <tr>
                {{ $header }}
            </tr>
        </thead>
        <tbody>
            {{ $body }}
        </tbody>
    </table>
</div> 