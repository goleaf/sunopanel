<x-app-layout>
    <x-slot name="header">
        {{ $playlist->title }}
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <x-button href="{{ route('playlists.index') }}" color="gray">
                        <x-icon name="arrow-left" class="h-5 w-5 mr-1" />
                        Back to Playlists
                    </x-button>
                    
                    <x-button href="{{ route('playlists.edit', $playlist) }}" color="yellow">
                        <x-icon name="pencil" class="h-5 w-5 mr-1" />
                        Edit Playlist
                    </x-button>
                </div>
                
                <div class="flex items-center">
                    <form action="{{ route('playlists.destroy', $playlist) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this playlist?');">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" color="red">
                            <x-icon name="trash" class="h-5 w-5 mr-1" />
                            Delete Playlist
                        </x-button>
                    </form>
                </div>
            </div>

            <div class="mb-8">
                <x-card rounded="lg">
                    <div class="md:flex">
                        <div class="md:flex-shrink-0 mb-4 md:mb-0 md:mr-6">
                            @if($playlist->cover_image)
                                <img src="{{ $playlist->cover_image }}" alt="{{ $playlist->title }}"
                                     class="h-48 w-48 object-cover rounded-lg">
                            @else
                                <div class="h-48 w-48 bg-indigo-100 flex items-center justify-center rounded-lg">
                                    <x-icon name="music-note" class="h-20 w-20 text-indigo-500" />
                                </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ $playlist->title }}</h2>
                            
                            @if($playlist->description)
                                <p class="text-gray-700 mb-4">{{ $playlist->description }}</p>
                            @endif
                            
                            <div class="flex flex-wrap gap-6 mb-4">
                                <div>
                                    <span class="text-gray-500 text-sm">Tracks</span>
                                    <p class="font-semibold">{{ $playlist->tracks->count() }}</p>
                                </div>
                                
                                @if($playlist->genre)
                                    <div>
                                        <span class="text-gray-500 text-sm">Genre</span>
                                        <p class="font-semibold">{{ $playlist->genre->name }}</p>
                                    </div>
                                @endif
                                
                                <div>
                                    <span class="text-gray-500 text-sm">Created</span>
                                    <p class="font-semibold">{{ $playlist->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                            
                            @if($playlist->tracks->count() > 0)
                                <div class="mt-4">
                                    <x-button href="#tracklist" color="indigo">
                                        <x-icon name="collection" class="h-5 w-5 mr-1" />
                                        View Tracks
                                    </x-button>
                                </div>
                            @endif
                        </div>
                    </div>
                </x-card>
            </div>

            <div class="mb-8 flex flex-wrap justify-between items-center gap-4">
                <h2 class="text-2xl font-bold text-gray-900" id="tracklist">
                    @if($playlist->tracks->count() > 0)
                        Tracks ({{ $playlist->tracks->count() }})
                    @else
                        No tracks in this playlist
                    @endif
                </h2>
                
                <div class="flex gap-2">
                    @if($playlist->tracks->count() > 0)
                        <x-button href="#" id="playAllButton" color="primary" class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                            </svg>
                            Play All
                        </x-button>
                    @endif
                    
                    <x-button href="{{ route('playlists.add-tracks', $playlist) }}" color="primary" size="sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Tracks
                    </x-button>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-error mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
                <!-- Playlist Details -->
                <div class="lg:col-span-1">
                    <div class="card bg-base-100 shadow-xl mb-6">
                        <div class="card-body">
                            <h2 class="card-title">Playlist Details</h2>
                            
                            <div class="space-y-4">
                                @if($playlist->cover_image)
                                    <figure class="px-4 pt-4">
                                        <img src="{{ $playlist->cover_image }}" alt="{{ $playlist->title }}" 
                                            class="w-full rounded-lg shadow-md">
                                    </figure>
                                @endif
                                
                                @if($playlist->description)
                                    <div class="divider"></div>
                                    <div>
                                        <h4 class="text-sm font-medium opacity-70">Description</h4>
                                        <p class="mt-1">{{ $playlist->description }}</p>
                                    </div>
                                @endif
                                
                                <div class="divider"></div>
                                <div>
                                    <h4 class="text-sm font-medium opacity-70">Created</h4>
                                    <p class="mt-1">{{ $playlist->created_at->format('Y-m-d H:i') }}</p>
                                </div>
                                
                                <div class="divider"></div>
                                <div>
                                    <h4 class="text-sm font-medium opacity-70">Tracks</h4>
                                    <p class="mt-1">{{ $playlist->tracks->count() }}</p>
                                </div>
                                
                                <div class="divider"></div>
                                <form action="{{ route('playlists.destroy', $playlist) }}" method="POST" 
                                    onsubmit="return confirm('Are you sure you want to delete this playlist?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" color="error" class="w-full justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Delete Playlist
                                    </x-button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tracks List -->
                <div class="lg:col-span-3">
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="card-title">Tracks</h2>
                                <x-button href="{{ route('playlists.add-tracks', $playlist) }}" color="primary" size="sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Add Tracks
                                </x-button>
                            </div>

                            @if($playlist->tracks->isEmpty())
                                <div class="text-center py-8 opacity-70">
                                    <p>No tracks in this playlist yet.</p>
                                    <x-button href="{{ route('playlists.add-tracks', $playlist) }}" color="primary" class="mt-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Add Tracks to Playlist
                                    </x-button>
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="table table-zebra w-full">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Track</th>
                                                <th>Audio</th>
                                                <th>Genres</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($playlist->tracks as $track)
                                                <tr>
                                                    <td>{{ $track->pivot->position + 1 }}</td>
                                                    <td>
                                                        <div class="flex items-center">
                                                            <div class="avatar mr-3">
                                                                <div class="mask mask-squircle w-10 h-10">
                                                                    <img src="{{ $track->image_url }}" alt="{{ $track->title }}">
                                                                </div>
                                                            </div>
                                                            <div class="font-medium">{{ $track->title }}</div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <x-audio-player :track="$track" />
                                                    </td>
                                                    <td>
                                                        @if($track->genres->isNotEmpty())
                                                            <div class="flex flex-wrap gap-1">
                                                                @foreach($track->genres as $genre)
                                                                    <span class="badge badge-primary">
                                                                        {{ $genre->name }}
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <span class="text-xs opacity-70">No genres</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="flex gap-2">
                                                            <x-button href="{{ route('tracks.show', $track) }}" color="ghost" size="sm" icon>
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                </svg>
                                                            </x-button>
                                                            <form action="{{ route('playlists.remove-track', [$playlist->id, $track->id]) }}" method="POST" class="inline-flex">
                                                                @csrf
                                                                @method('DELETE')
                                                                <x-button type="submit" color="ghost" size="sm" icon class="text-error" onclick="return confirm('Are you sure you want to remove this track from the playlist?')">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                    </svg>
                                                                </x-button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
