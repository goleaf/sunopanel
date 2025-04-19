@props([
    'title' => null,
    'subtitle' => null,
    'image' => null,
    'bordered' => true,
    'compact' => false,
    'class' => ''
])

<div class="card {{ $bordered ? 'card-bordered' : '' }} {{ $compact ? 'card-compact' : '' }} bg-base-100 shadow-xl {{ $class }}">
    @if($image)
        <figure>{!! $image !!}</figure>
    @endif
    <div class="card-body">
        @if($title)
            <h2 class="card-title">{{ $title }}</h2>
        @endif
        @if($subtitle)
            <p class="text-base-content/70">{{ $subtitle }}</p>
        @endif
        <div>
            {{ $slot }}
        </div>
    </div>
</div> 