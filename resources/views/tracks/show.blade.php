@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-base-content">{{ $track->title }}</h1>
        <div class="flex space-x-2">
            <a href="{{ route('tracks.edit', $track->id) }}" class="btn btn-primary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit Track
            </a>
            
            <a href="{{ route('tracks.play', $track->id) }}" target="_blank" class="btn btn-success btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Play
            </a>
            
            <form action="{{ route('tracks.destroy', $track->id) }}" method="POST" class="inline-block">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-error btn-sm" onclick="return confirm('Are you sure you want to delete this track?')">
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
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Track Card -->
        <div class="card bg-base-100 shadow-md">
            <figure>
                <img src="{{ $track->image_url }}" alt="{{ $track->title }}" class="w-full h-auto object-cover">
            </figure>
            <div class="card-body">
                <h2 class="card-title border-b pb-2">Track Details</h2>
                <div class="space-y-3 mt-2">
                    <div>
                        <span class="text-sm font-medium opacity-70">Name:</span>
                        <p class="text-sm">{{ $track->title }}</p>
                    </div>
                    <div>
                        <span class="text-sm font-medium opacity-70">Unique ID:</span>
                        <p class="text-sm">{{ $track->unique_id }}</p>
                    </div>
                    <div>
                        <span class="text-sm font-medium opacity-70">Added on:</span>
                        <p class="text-sm">{{ $track->created_at->format('M d, Y') }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <h3 class="text-lg font-semibold border-b pb-2">Genres</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if($track->genres && count($track->genres) > 0)
                            @foreach($track->genres as $genre)
                                <a href="{{ route('genres.show', $genre->id) }}" class="badge badge-primary">
                                    {{ $genre->name }}
                                </a>
                            @endforeach
                        @else
                            <span class="text-sm opacity-70">No genres assigned</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Player and Info -->
        <div class="md:col-span-2 space-y-6">
            <!-- Audio Player Card -->
            <div class="card bg-base-100 shadow-md">
                <div class="card-body">
                    <h2 class="card-title">Media Player</h2>
                    <div class="p-4 bg-base-200 rounded-lg">
                        @if(pathinfo($track->audio_url, PATHINFO_EXTENSION) === 'mp4')
                            <video controls class="w-full" id="mp4-player">
                                <source src="{{ $track->audio_url }}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        @else
                            <audio controls class="w-full" id="audio-player">
                                <source src="{{ $track->audio_url }}" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                        @endif
                    </div>
                    <div class="mt-3 flex justify-between text-xs opacity-70">
                        <span id="current-time">0:00</span>
                        <span id="duration">{{ $track->duration ?? '0:00' }}</span>
                    </div>
                </div>
            </div>

            <!-- Media URLs Card -->
            <div class="card bg-base-100 shadow-md">
                <div class="card-body">
                    <h2 class="card-title">Media Details</h2>
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <tbody>
                                <tr>
                                    <td class="font-medium">Audio URL:</td>
                                    <td class="break-all">
                                        <a href="{{ $track->audio_url }}" target="_blank" class="link link-primary">
                                            {{ $track->audio_url }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-medium">Image URL:</td>
                                    <td class="break-all">
                                        <a href="{{ $track->image_url }}" target="_blank" class="link link-primary">
                                            {{ $track->image_url }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-medium">Duration:</td>
                                    <td>{{ $track->duration }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Playlists Card -->
            <div class="card bg-base-100 shadow-md">
                <div class="card-body">
                    <h2 class="card-title">In Playlists</h2>
                    @if($track->playlists && count($track->playlists) > 0)
                        <div class="overflow-x-auto">
                            <table class="table w-full">
                                <tbody>
                                    @foreach($track->playlists as $playlist)
                                        <tr>
                                            <td>
                                                <a href="{{ route('playlists.show', $playlist) }}" class="link link-primary">
                                                    {{ $playlist->name }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm opacity-70">This track is not in any playlists.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('tracks.index') }}" class="btn btn-ghost">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Tracks
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mediaPlayer = document.getElementById('mp4-player') || document.getElementById('audio-player');
    const currentTimeEl = document.getElementById('current-time');
    const durationEl = document.getElementById('duration');
    
    if (mediaPlayer) {
        // Format time function
        const formatTime = (seconds) => {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
        };
        
        // Update time display
        mediaPlayer.addEventListener('timeupdate', () => {
            currentTimeEl.textContent = formatTime(mediaPlayer.currentTime);
            if (!isNaN(mediaPlayer.duration)) {
                durationEl.textContent = formatTime(mediaPlayer.duration);
            }
        });
        
        // Update when metadata is loaded
        mediaPlayer.addEventListener('loadedmetadata', () => {
            if (!isNaN(mediaPlayer.duration)) {
                durationEl.textContent = formatTime(mediaPlayer.duration);
            }
        });
    }
});
</script>
@endsection
