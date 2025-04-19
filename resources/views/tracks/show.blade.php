@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary">{{ $track->title }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('tracks.edit', $track) }}" class="btn btn-sm btn-warning">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Edit
            </a>
            <a href="{{ route('tracks.play', $track) }}" target="_blank" class="btn btn-sm btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Play
            </a>
            <form action="{{ route('tracks.destroy', $track) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-error" onclick="return confirm('Are you sure you want to delete this track?')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex items-start gap-4">
                        <div class="avatar">
                            <div class="w-32 rounded-lg">
                                <img src="{{ $track->image_url }}" alt="{{ $track->title }}" />
                            </div>
                        </div>
                        <div class="space-y-2 flex-1">
                            <h3 class="text-2xl font-semibold">{{ $track->title }}</h3>
                            <div class="badge badge-outline">{{ $track->unique_id }}</div>
                            <div class="text-sm opacity-70">Added on {{ $track->created_at->format('F j, Y') }}</div>
                            
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach($track->genres as $genre)
                                    <a href="{{ route('genres.show', $genre) }}" class="badge badge-primary">
                                        {{ $genre->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Media Player</h3>
                            
                            @php
                                $isAudio = str_contains($track->audio_url, '.mp3') || 
                                          str_contains($track->audio_url, '.wav') || 
                                          str_contains($track->audio_url, '.ogg');
                                
                                $isVideo = str_contains($track->audio_url, '.mp4') || 
                                          str_contains($track->audio_url, '.webm') || 
                                          str_contains($track->audio_url, '.mov');
                            @endphp
                            
                            <div class="card bg-base-200">
                                <div class="card-body p-4">
                                    @if($isAudio)
                                        <audio controls class="w-full" preload="metadata">
                                            <source src="{{ $track->audio_url }}" type="audio/mpeg">
                                            Your browser does not support the audio element.
                                        </audio>
                                    @elseif($isVideo)
                                        <video controls class="w-full max-h-[500px]" preload="metadata">
                                            <source src="{{ $track->audio_url }}" type="video/mp4">
                                            Your browser does not support the video element.
                                        </video>
                                    @else
                                        <div class="alert alert-warning">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <span>Unknown media format. Direct link: <a href="{{ $track->audio_url }}" target="_blank" class="link link-primary">Open Media</a></span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="space-y-6">
            <!-- Media Details -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h3 class="text-lg font-semibold card-title">Media Details</h3>
                    
                    <div class="space-y-3 mt-2">
                        <div>
                            <div class="text-sm font-semibold text-primary">Audio URL</div>
                            <a href="{{ $track->audio_url }}" target="_blank" class="link link-primary text-sm break-all">
                                {{ $track->audio_url }}
                            </a>
                        </div>
                        
                        <div>
                            <div class="text-sm font-semibold text-primary">Image URL</div>
                            <a href="{{ $track->image_url }}" target="_blank" class="link link-primary text-sm break-all">
                                {{ $track->image_url }}
                            </a>
                        </div>
                        
                        @if($track->duration)
                        <div>
                            <div class="text-sm font-semibold text-primary">Duration</div>
                            <div class="text-sm">{{ $track->duration }}</div>
                        </div>
                        @endif
                        
                        <div>
                            <div class="text-sm font-semibold text-primary">Unique ID</div>
                            <div class="text-sm font-mono">{{ $track->unique_id }}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- In Playlists -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h3 class="text-lg font-semibold card-title">In Playlists</h3>
                    
                    @if($track->playlists->count() > 0)
                        <ul class="space-y-2">
                            @foreach($track->playlists as $playlist)
                                <li>
                                    <a href="{{ route('playlists.show', $playlist) }}" class="btn btn-outline btn-sm w-full justify-start">
                                        <span class="truncate">{{ $playlist->name }}</span>
                                        <span class="badge badge-neutral">Position: {{ $playlist->pivot->position }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-sm opacity-70">This track is not in any playlists yet.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-6">
        <a href="{{ route('tracks.index') }}" class="btn btn-ghost">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            Back to Tracks
        </a>
    </div>
</div>
@endsection
