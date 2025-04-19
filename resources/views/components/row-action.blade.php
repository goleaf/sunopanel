@props(['href' => '#', 'icon' => null, 'label' => '', 'color' => 'primary', 'size' => 'xs'])

<x-button :href="$href" :color="$color" :size="$size" {{ $attributes }}>
    @if($icon)
        <span class="mr-1">
            {!! $icon !!}
        </span>
    @endif
    {{ $label ?? $slot }}
</x-button> 