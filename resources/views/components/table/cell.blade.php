@props(['colspan' => null, 'align' => 'left'])

<td {{ $attributes->merge(['class' => 'px-6 py-4 whitespace-nowrap text-' . $align]) }} @if($colspan) colspan="{{ $colspan }}" @endif>
    <div class="text-sm text-gray-900">
        {{ $slot }}
    </div>
</td> 