@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <x-page-header title="{{ $track->title }}">
        <x-slot name="buttons">
            <x-button href="{{ route('tracks.edit', $track->id) }}" color="blue" class="mr-2">
                <x-icon name="pencil" class="-ml-1 mr-2 h-5 w-5" />
                Edit Track
            </x-button>
            
            <x-button href="{{ route('tracks.play', $track->id) }}" target="_blank" color="green" class="mr-2">
                <x-icon name="play" class="-ml-1 mr-2 h-5 w-5" />
                Play
            </x-button>
            
            <form action="{{ route('tracks.destroy', $track->id) }}" method="POST" class="inline-block">
                @csrf
                @method('DELETE')
                <x-button type="submit" color="red" onclick="return confirm('Are you sure you want to delete this track?')" class="cursor-pointer">
                    <x-icon name="trash" class="-ml-1 mr-2 h-5 w-5" />
                    Delete
                </x-button>
            </form>
        </x-slot>
    </x-page-header>

    @if(session('success'))
        <x-alert type="success" :message="session('success')" class="mb-4" />
    @endif

    @if(session('error'))
        <x-alert type="error" :message="session('error')" class="mb-4" />
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Track Card -->
        <div class="bg-white rounded-lg overflow-hidden border border-gray-200">
            <div class="aspect-w-1 aspect-h-1 w-full">
                <img src="{{ $track->image_url }}" alt="{{ $track->title }}" class="w-full h-auto object-cover">
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Track Details</h2>
                    <div class="mt-4 space-y-3">
                        <div>
                            <span class="text-sm font-medium text-gray-500">Name:</span>
                            <p class="mt-1 text-sm text-gray-900">{{ $track->title }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Unique ID:</span>
                            <p class="mt-1 text-sm text-gray-900">{{ $track->unique_id }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Added on:</span>
                            <p class="mt-1 text-sm text-gray-900">{{ $track->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Genres</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if($track->genres && count($track->genres) > 0)
                            @foreach($track->genres as $genre)
                                <a href="{{ route('genres.show', $genre->id) }}" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $genre->name }}
                                </a>
                            @endforeach
                        @else
                            <span class="text-sm text-gray-500">No genres assigned</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Player and Info -->
        <div class="md:col-span-2 space-y-6">
            <!-- Audio Player Card -->
            <div class="bg-white rounded-lg overflow-hidden border border-gray-200">
                <div class="border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 px-6 py-4">Media Player</h2>
                </div>
                <div class="p-6">
                    <div class="p-4 bg-gray-50 rounded-lg">
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
                    <div class="mt-3 flex justify-between text-xs text-gray-500">
                        <span id="current-time">0:00</span>
                        <span id="duration">{{ $track->duration ?? '0:00' }}</span>
                    </div>
                </div>
            </div>

            <!-- Media URLs Card -->
            <div class="bg-white rounded-lg overflow-hidden border border-gray-200">
                <div class="border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 px-6 py-4">Media Details</h2>
                </div>
                <div class="p-6">
                    <dl class="divide-y divide-gray-200">
                        <div class="py-3 flex flex-col sm:flex-row">
                            <dt class="text-sm font-medium text-gray-500 sm:w-40 sm:flex-shrink-0">Audio URL:</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:ml-6 break-all">
                                <a href="{{ $track->audio_url }}" target="_blank" class="text-indigo-600">
                                    {{ $track->audio_url }}
                                </a>
                            </dd>
                        </div>
                        <div class="py-3 flex flex-col sm:flex-row">
                            <dt class="text-sm font-medium text-gray-500 sm:w-40 sm:flex-shrink-0">Image URL:</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:ml-6 break-all">
                                <a href="{{ $track->image_url }}" target="_blank" class="text-indigo-600">
                                    {{ $track->image_url }}
                                </a>
                            </dd>
                        </div>
                        <div class="py-3 flex flex-col sm:flex-row">
                            <dt class="text-sm font-medium text-gray-500 sm:w-40 sm:flex-shrink-0">Duration:</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:ml-6">
                                {{ $track->duration }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Playlists Card -->
            <div class="bg-white rounded-lg overflow-hidden border border-gray-200">
                <div class="border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 px-6 py-4">In Playlists</h2>
                </div>
                <div class="p-6">
                    @if($track->playlists && count($track->playlists) > 0)
                        <ul class="divide-y divide-gray-200">
                            @foreach($track->playlists as $playlist)
                                <li class="py-2">
                                    <a href="{{ route('playlists.show', $playlist) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ $playlist->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500">This track is not in any playlists.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6">
        <x-button href="{{ route('tracks.index') }}" color="gray" class="flex items-center">
            <x-icon name="chevron-left" class="mr-2 h-5 w-5" />
            Back to Tracks
        </x-button>
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
