<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-base-content">
            {{ $playlist->name }}
            @if($playlist->genre)
                <span class="badge badge-success ml-2">
                    {{ $playlist->genre->name }}
                </span>
            @endif
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <div class="flex flex-wrap gap-2 mb-6">
            <a href="{{ route('playlists.index') }}" class="btn btn-ghost">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Playlists
            </a>
            <a href="{{ route('playlists.add-tracks', $playlist) }}" class="btn btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Tracks
            </a>
            <a href="{{ route('playlists.edit', $playlist) }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
            </a>
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
                                    <img src="{{ $playlist->cover_image }}" alt="{{ $playlist->name }}" 
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
                                <button type="submit" class="btn btn-error w-full justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete Playlist
                                </button>
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
                            <a href="{{ route('playlists.add-tracks', $playlist) }}" class="btn btn-primary btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Tracks
                            </a>
                        </div>

                        @if($playlist->tracks->isEmpty())
                            <div class="text-center py-8 opacity-70">
                                <p>No tracks in this playlist yet.</p>
                                <a href="{{ route('playlists.add-tracks', $playlist) }}" class="btn btn-primary mt-4">
                                    Add Tracks to Playlist
                                </a>
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
                                                        <a href="{{ route('tracks.show', $track) }}" class="btn btn-circle btn-sm btn-ghost">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                                            </svg>
                                                        </a>
                                                        <form action="{{ route('playlists.remove-track', [$playlist->id, $track->id]) }}" method="POST" class="inline-flex">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" 
                                                                class="btn btn-circle btn-sm btn-ghost text-error" 
                                                                onclick="return confirm('Are you sure you want to remove this track from the playlist?')">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                                </svg>
                                                            </button>
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
</x-app-layout>
