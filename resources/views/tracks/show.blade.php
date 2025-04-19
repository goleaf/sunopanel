@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<div class="flex justify-between items-center mb-6">
    {{-- Use breadcrumbs for better context --}}
    <div class="breadcrumbs text-sm">
        <ul>
            <li><a href="{{ route('tracks.index') }}">Tracks</a></li> 
            <li class="truncate" title="{{ $track->title }}">{{ Str::limit($track->title, 40) }}</li>
        </ul>
    </div>
    <x-button 
        href="{{ route('tracks.index') }}" 
        variant="outline"
        size="sm"
    >
        <x-icon name="arrow-sm-left" size="4" class="mr-1" />
        Back to Tracks
    </x-button>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    {{-- Left Column: Cover Art & Primary Actions --}}
    <div class="md:col-span-1 space-y-6">
        {{-- Cover Art Card --}}
        <div class="card bg-base-100 shadow-xl overflow-hidden">
             @if($track->image_url)
                <figure><img src="{{ $track->image_url }}" alt="{{ $track->title }}" class="w-full h-auto object-cover" onerror="this.style.display='none'; this.parentElement.innerHTML = '<div class=\"aspect-square flex items-center justify-center bg-base-200\"><x-icon name=\"photo\" class=\"h-16 w-16 text-base-content/30\" /></div>'"></figure>
            @else
                <div class="aspect-square flex items-center justify-center bg-base-200">
                    <x-icon name="photo" class="h-16 w-16 text-base-content/30" />
                </div>
            @endif
        </div>

        {{-- Primary Actions Card --}}
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body items-center text-center p-4 space-y-2">
                 <x-audio-player :track="$track" /> {{-- Moved audio player here --}}
                 <div class="card-actions justify-center w-full pt-2">
                     <x-tooltip text="Edit Track" position="top">
                        <x-button href="{{ route('tracks.edit', $track) }}" variant="outline" size="sm" icon>
                            <x-icon name="pencil" />
                        </x-button>
                     </x-tooltip>
                     <x-tooltip text="Delete Track" position="top">
                        <form action="{{ route('tracks.destroy', $track) }}" method="POST" onsubmit="return confirm('Delete track: {{ addslashes($track->title) }}?')" class="inline">
                            @csrf
                            @method('DELETE')
                            <x-button type="submit" variant="outline" color="error" size="sm" icon>
                                <x-icon name="trash" />
                            </x-button>
                        </form>
                     </x-tooltip>
                 </div>
            </div>
        </div>
    </div>

    {{-- Right Column: Details & Playlists --}}
    <div class="md:col-span-2 space-y-6">
        {{-- Track Details Card --}}
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h1 class="card-title text-3xl mb-2">{{ $track->title }}</h1>
                {{-- Genre Badges --}}
                @if($track->genres->count() > 0)
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach($track->genres as $genre)
                            <div class="badge badge-outline">{{ $genre->name }}</div>
                        @endforeach
                    </div>
                @endif

                {{-- Using Definition List for details --}}
                <dl class="divide-y divide-base-200 text-sm">
                    <div class="py-3 grid grid-cols-3 gap-4">
                        <dt class="font-medium text-base-content/70">Duration</dt>
                        <dd class="text-base-content col-span-2">{{ formatDuration($track->duration_seconds ?: $track->duration) }}</dd>
                    </div>
                     <div class="py-3 grid grid-cols-3 gap-4">
                        <dt class="font-medium text-base-content/70">Audio URL</dt>
                        <dd class="text-base-content col-span-2 truncate">
                            <a href="{{ $track->audio_url }}" target="_blank" class="link link-hover link-primary" title="{{ $track->audio_url }}">
                                {{ $track->audio_url }}
                            </a>
                        </dd>
                    </div>
                     <div class="py-3 grid grid-cols-3 gap-4">
                        <dt class="font-medium text-base-content/70">Added</dt>
                        <dd class="text-base-content col-span-2" title="{{ $track->created_at->format('Y-m-d H:i:s') }}">{{ $track->created_at->diffForHumans() }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                        <dt class="font-medium text-base-content/70">Last Updated</dt>
                        <dd class="text-base-content col-span-2" title="{{ $track->updated_at->format('Y-m-d H:i:s') }}">{{ $track->updated_at->diffForHumans() }}</dd>
                    </div>
                    {{-- Add more fields as needed (e.g., Artist, Album) --}}
                </dl>
            </div>
        </div>

        {{-- Playlists Card --}}
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">Part of Playlists ({{ $track->playlists->count() }})</h2>
                @if($track->playlists->isEmpty())
                    <div class="text-center py-6 text-base-content/70 italic">
                        This track hasn't been added to any playlists yet.
                    </div>
                @else
                    <ul class="divide-y divide-base-200">
                        @foreach($track->playlists as $playlist)
                            <li class="py-3">
                                <a href="{{ route('playlists.show', $playlist) }}" class="flex items-center space-x-3 group">
                                    <div class="avatar">
                                        <div class="mask mask-squircle w-10 h-10">
                                            @if($playlist->artwork_url)
                                                <img src="{{ $playlist->artwork_url }}" alt="{{ $playlist->title }}" onerror="this.style.display='none'; this.parentElement.innerHTML = '<div class=\"flex items-center justify-center w-full h-full bg-base-200\"><x-icon name=\"collection\" class=\"h-5 w-5 text-base-content/30\" /></div>'" />
                                            @else
                                                 <div class="flex items-center justify-center w-full h-full bg-base-200">
                                                    <x-icon name="collection" class="h-5 w-5 text-base-content/30" />
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <p class="font-medium group-hover:text-primary transition duration-150">{{ $playlist->title }}</p>
                                        <p class="text-xs text-base-content/70">{{ $playlist->tracks_count }} tracks</p>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
