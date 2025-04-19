@props(['align' => 'left', 'label' => ''])

<td {{ $attributes->merge(['class' => 'px-4 py-3 whitespace-nowrap text-sm text-base-content ' . ($align === 'left' ? 'text-left' : ($align === 'right' ? 'text-right' : 'text-center'))]) }} 
    @if($label) data-label="{{ $label }}" @endif>
    {{ $slot }}
</td> 