@props([
    'header', 
    'body', 
    'striped' => false, 
    'hoverable' => true, 
    'compact' => false,
    'responsive' => true,
    'adaptiveLayout' => true,
    'tableClasses' => ''
])

<div class="table-responsive-wrapper bg-base-100 rounded-lg shadow">
    <table class="table w-full {{ $tableClasses }}
        {{ $striped ? 'table-zebra' : '' }}
        {{ $hoverable ? 'table-hover' : '' }}
        {{ $compact ? 'table-sm' : '' }}
        {{ $adaptiveLayout ? 'table-adaptive' : '' }}
    ">
        <thead class="{{ $adaptiveLayout ? 'hidden md:table-header-group' : '' }}">
            <tr>
                {{ $header }}
            </tr>
        </thead>
        <tbody class="{{ $adaptiveLayout ? 'table-compact-mobile' : '' }}">
            {{ $body }}
        </tbody>
    </table>
</div> 