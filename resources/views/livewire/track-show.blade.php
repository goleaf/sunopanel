<div>
    <div class="container px-4 py-6 mx-auto">
        <!-- Header and actions -->
        <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $track->title }}</h1>
                <div class="text-sm breadcrumbs">
                    <ul>
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li><a href="{{ route('tracks.index') }}">Tracks</a></li>
                        <li>{{ $track->title }}</li>
                    </ul>
                </div>
            </div>
            <div class="flex gap-2 mt-4 md:mt-0">
                <button wire:click="playTrack" class="btn btn-primary gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                    </svg>
                    Play
                </button>
                <a href="{{ route('tracks.edit', $track->id) }}" class="btn btn-outline gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                    </svg>
                    Edit
                </a>
                <button wire:click="$toggle('showDeleteModal')" class="btn btn-outline btn-error gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    Delete
                </button>
            </div>
        </div>

        @if (session()->has('message'))
            <div class="alert alert-success mb-4">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-error mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Track details -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Track image -->
            <div class="md:col-span-1">
                <div class="card bg-base-100 shadow-xl">
                    <figure class="px-6 pt-6">
                        @if($track->image_url)
                            <img src="{{ $track->image_url }}" alt="{{ $track->title }}" class="rounded-xl w-full object-cover aspect-square">
                        @else
                            <div class="bg-gray-200 rounded-xl w-full aspect-square flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                </svg>
                            </div>
                        @endif
                    </figure>
                    <div class="card-body">
                        @if($track->audio_url)
                            <div class="text-sm text-gray-500 mt-2 truncate">
                                <span class="font-semibold">Audio URL:</span>
                                <a href="{{ $track->audio_url }}" target="_blank" class="link link-hover text-primary">
                                    {{ $track->audio_url }}
                                </a>
                            </div>
                        @else
                            <div class="text-sm text-gray-500 mt-2">
                                <span class="font-semibold">Audio URL:</span> Not available
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Track info -->
            <div class="md:col-span-2">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">Track Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <div class="text-sm text-gray-500">Title</div>
                                <div class="font-semibold">{{ $track->title }}</div>
                            </div>
                            
                            <div>
                                <div class="text-sm text-gray-500">Duration</div>
                                <div class="font-semibold">
                                    @if($track->duration)
                                        {{ $track->duration }}
                                    @else
                                        Not specified
                                    @endif
                                </div>
                            </div>
                            
                            <div>
                                <div class="text-sm text-gray-500">Created</div>
                                <div class="font-semibold">{{ $track->created_at->format('Y-m-d H:i') }}</div>
                            </div>
                            
                            <div>
                                <div class="text-sm text-gray-500">Last Updated</div>
                                <div class="font-semibold">{{ $track->updated_at->format('Y-m-d H:i') }}</div>
                            </div>
                        </div>

                        <!-- Genres -->
                        <div class="mt-6">
                            <h3 class="font-semibold mb-2">Genres</h3>
                            @if($track->genres->count() > 0)
                                <div class="flex flex-wrap gap-2">
                                    @foreach($track->genres as $genre)
                                        <span class="badge badge-primary">{{ $genre->name }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-sm">No genres assigned to this track.</p>
                            @endif
                        </div>

                        <!-- Playlists -->
                        <div class="mt-6">
                            <h3 class="font-semibold mb-2">Added to Playlists</h3>
                            @if($track->playlists->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="table table-zebra w-full">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Tracks</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($track->playlists as $playlist)
                                                <tr>
                                                    <td>{{ $playlist->name }}</td>
                                                    <td>{{ $playlist->tracks_count ?? $playlist->tracks->count() }}</td>
                                                    <td>
                                                        <a href="{{ route('playlists.show', $playlist->id) }}" class="btn btn-xs btn-outline">
                                                            View
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-gray-500 text-sm">This track is not added to any playlists yet.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" x-data x-show="$wire.showDeleteModal" :class="{ 'modal-open': $wire.showDeleteModal }">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-error">Delete Track</h3>
            <p class="py-4">Are you sure you want to delete "{{ $track->title }}"? This action cannot be undone.</p>
            <div class="modal-action">
                <button class="btn" wire:click="$toggle('showDeleteModal')">Cancel</button>
                <button class="btn btn-error" wire:click="deleteTrack">Delete</button>
            </div>
        </div>
    </div>

    <!-- Audio Player -->
    <div x-data="{
        audioPlayer: null,
        isPlaying: false,
        currentTrack: null,
        
        initPlayer() {
            window.addEventListener('playTrack', event => {
                this.playAudio(event.detail.url, event.detail.title);
            });
        },
        
        playAudio(url, title) {
            if (this.audioPlayer) {
                this.audioPlayer.pause();
                this.audioPlayer = null;
            }
            
            this.audioPlayer = new Audio(url);
            this.currentTrack = title;
            this.audioPlayer.play();
            this.isPlaying = true;
            
            this.audioPlayer.addEventListener('ended', () => {
                this.isPlaying = false;
            });
        },
        
        togglePlayPause() {
            if (!this.audioPlayer) return;
            
            if (this.isPlaying) {
                this.audioPlayer.pause();
                this.isPlaying = false;
            } else {
                this.audioPlayer.play();
                this.isPlaying = true;
            }
        }
    }" x-init="initPlayer()" x-show="currentTrack" x-cloak
    class="fixed bottom-0 left-0 w-full bg-base-200 border-t border-base-300 p-2">
        <div class="container mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button @click="togglePlayPause()" class="btn btn-circle btn-sm">
                    <template x-if="isPlaying">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </template>
                    <template x-if="!isPlaying">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                        </svg>
                    </template>
                </button>
                <div>
                    <div class="text-sm font-medium" x-text="currentTrack"></div>
                    <div class="text-xs text-gray-500">Now Playing</div>
                </div>
            </div>
        </div>
    </div>
</div> 