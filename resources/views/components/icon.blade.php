@props(['name', 'class' => 'w-4 h-4'])

@php
    $icons = config('navigation.icons');
    $path = $icons[$name] ?? '';
    
    // Special handling for YouTube icon which has both path and polygon
    $isYoutube = $name === 'youtube';
    $isYoutubePlay = $name === 'youtube-play';
    $isCogWithInner = $name === 'cog';
@endphp

<svg {{ $attributes->merge(['class' => $class]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
    @if($isYoutube)
        <path d="{{ $path }}"></path>
        <polygon points="{{ config('navigation.icons.youtube-play') }}"></polygon>
    @elseif($isYoutubePlay)
        <polygon points="{{ $path }}"></polygon>
    @elseif($isCogWithInner)
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}"></path>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ config('navigation.icons.cog-inner') }}"></path>
    @else
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}"></path>
    @endif
</svg> 