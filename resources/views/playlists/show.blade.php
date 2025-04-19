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
                            <x-icon name="play" size="5" />
                            Play All
                        </x-button>
                    @endif
                    
                    <x-button href="{{ route('playlists.add-tracks', $playlist) }}" color="primary" size="sm">
                        <x-icon name="plus" size="4" class="mr-1" />
                        Add Tracks
                    </x-button>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success mb-4">
                    <x-icon name="check" size="6" />
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-error mb-4">
                    <x-icon name="x" size="6" />
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
                                        <x-icon name="trash" size="5" class="mr-2" />
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
                                    <x-icon name="plus" size="4" class="mr-1" />
                                    Add Tracks
                                </x-button>
                            </div>

                            @if($playlist->tracks->isEmpty())
                                <div class="text-center py-8 opacity-70">
                                    <p>No tracks in this playlist yet.</p>
                                    <x-button href="{{ route('playlists.add-tracks', $playlist) }}" color="primary" class="mt-4">
                                        <x-icon name="plus" size="5" class="mr-2" />
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
                                                                <x-icon name="eye" size="4" />
                                                            </x-button>
                                                            <form action="{{ route('playlists.remove-track', [$playlist->id, $track->id]) }}" method="POST" class="inline-flex">
                                                                @csrf
                                                                @method('DELETE')
                                                                <x-button type="submit" color="ghost" size="sm" icon class="text-error" onclick="return confirm('Are you sure you want to remove this track from the playlist?')">
                                                                    <x-icon name="trash" size="4" />
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
