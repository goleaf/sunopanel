<x-app-layout>
    <x-slot name="header">
        {{ $playlist->title }}
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div class="breadcrumbs text-sm">
                    <ul>
                        <li><a href="{{ route('playlists.index') }}">Playlists</a></li> 
                        <li class="truncate" title="{{ $playlist->title }}">{{ Str::limit($playlist->title, 40) }}</li>
                    </ul>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-button href="{{ route('playlists.addTracks', $playlist) }}" variant="outline" size="sm">
                        <x-icon name="adjustments" size="4" class="mr-1" />
                        Add/Remove Tracks
                    </x-button>
                    <x-button href="{{ route('playlists.edit', $playlist) }}" variant="outline" size="sm">
                        <x-icon name="pencil" size="4" class="mr-1" />
                        Edit Details
                    </x-button>
                    <form action="{{ route('playlists.destroy', $playlist) }}" method="POST" onsubmit="return confirm('Delete playlist: {{ addslashes($playlist->title) }}?')" class="inline">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="outline" color="error" size="sm">
                            <x-icon name="trash" size="4" class="mr-1" />
                            Delete Playlist
                        </x-button>
                    </form>
                    <x-button href="{{ route('playlists.index') }}" variant="ghost" size="sm">
                        <x-icon name="arrow-sm-left" size="4" class="mr-1" />
                        Back
                    </x-button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 space-y-6">
                    <div class="card bg-base-100 shadow-xl">
                        <figure class="px-6 pt-6">
                            <img src="{{ $playlist->cover_image ?? asset('images/no-playlist-image.png') }}" 
                                alt="{{ $playlist->title }}" 
                                class="w-full h-auto object-cover rounded-lg shadow aspect-square" 
                                onerror="this.src='{{ asset('images/no-playlist-image.png') }}'" />
                        </figure>
                        <div class="card-body">
                            <h2 class="card-title text-2xl">{{ $playlist->title }}</h2>
                            <p class="text-sm text-base-content/70">{{ $playlist->description ?: 'No description available.' }}</p>
                            <div class="divider my-2"></div>
                            <dl class="text-sm space-y-2">
                                <div class="flex justify-between">
                                    <dt class="font-medium text-base-content/70">Tracks</dt>
                                    <dd class="text-base-content">{{ $playlist->tracks->count() }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="font-medium text-base-content/70">Total Duration</dt>
                                    <dd class="text-base-content">{{-- Calculation needed here --}} TBD</dd> 
                                </div>
                                @if($playlist->genre)
                                    <div class="flex justify-between">
                                        <dt class="font-medium text-base-content/70">Genre</dt>
                                        <dd class="text-base-content">
                                            <a href="{{ route('genres.show', $playlist->genre) }}" class="badge badge-outline badge-sm hover:bg-base-300">{{ $playlist->genre->name }}</a>
                                        </dd>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <dt class="font-medium text-base-content/70">Created</dt>
                                    <dd class="text-base-content" title="{{ $playlist->created_at->format('Y-m-d H:i:s') }}">{{ $playlist->created_at->diffForHumans() }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="font-medium text-base-content/70">Updated</dt>
                                    <dd class="text-base-content" title="{{ $playlist->updated_at->format('Y-m-d H:i:s') }}">{{ $playlist->updated_at->diffForHumans() }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <h2 class="card-title text-xl mb-4">Tracks in Playlist ({{ $playlist->tracks->count() }})</h2>
                            
                            @if($playlist->tracks->isEmpty())
                                <div class="text-center py-10 text-base-content/70 italic">
                                    No tracks in this playlist yet. Add some using the button above.
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="table table-zebra table-sm w-full">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Title</th>
                                                <th>Genres</th>
                                                <th>Duration</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($playlist->tracks->sortBy('pivot.position') as $index => $track)
                                                <tr>
                                                    <td class="text-base-content/70">{{ $index + 1 }}</td>
                                                    <td>
                                                        <div class="flex items-center space-x-3">
                                                            <div class="avatar">
                                                                <div class="mask mask-squircle w-10 h-10">
                                                                    <img src="{{ $track->image_url ?? asset('images/no-image.jpg') }}" 
                                                                        alt="{{ $track->title }}" 
                                                                        onerror="this.src='{{ asset('images/no-image.jpg') }}'" />
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <a href="{{ route('tracks.show', $track) }}" class="font-medium hover:text-primary transition duration-150 ease-in-out">
                                                                    {{ Str::limit($track->title, 50) }}
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="flex flex-wrap gap-1">
                                                            @foreach($track->genres->take(2) as $genre)
                                                                <a href="{{ route('genres.show', $genre) }}" class="badge badge-ghost badge-sm hover:bg-base-300">{{ $genre->name }}</a>
                                                            @endforeach
                                                            @if($track->genres->count() > 2)
                                                                <div class="badge badge-ghost badge-sm">...</div>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        {{ formatDuration($track->duration_seconds ?: $track->duration) }}
                                                    </td>
                                                    <td>
                                                        <div class="flex items-center space-x-1">
                                                            <x-tooltip text="Play Track" position="top">
                                                                <x-button href="{{ route('tracks.play', $track) }}" variant="ghost" size="xs" icon>
                                                                    <x-icon name="play" />
                                                                </x-button>
                                                            </x-tooltip>
                                                            <x-tooltip text="View Track Details" position="top">
                                                                <x-button href="{{ route('tracks.show', $track) }}" variant="ghost" size="xs" icon>
                                                                    <x-icon name="eye" />
                                                                </x-button>
                                                            </x-tooltip>
                                                            <x-tooltip text="Remove from Playlist" position="top">
                                                                <form action="{{ route('playlists.removeTrack', [$playlist, $track]) }}" method="POST" onsubmit="return confirm('Remove \'{{ addslashes($track->title) }}\' from this playlist?')" class="inline">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <x-button type="submit" variant="ghost" color="error" size="xs" icon>
                                                                        <x-icon name="trash" />
                                                                    </x-button>
                                                                </form>
                                                            </x-tooltip>
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
