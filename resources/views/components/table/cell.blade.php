@props(['colspan' => null, 'align' => 'left'])

<td 
    {{ $attributes->merge(['class' => 'text-' . $align]) }} 
    @if($colspan) colspan="{{ $colspan }}" @endif
>
    {{ $slot }}
</td> 