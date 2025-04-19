<x-app-layout>
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-base-content">Playlists</h1>
        <div class="flex space-x-2 w-full sm:w-auto">
             {{-- Search Form --}}
             <form action="{{ route('playlists.index') }}" method="GET" class="join flex-grow">
                 <input type="text" name="search" placeholder="Search playlists..." value="{{ request('search') }}" class="input input-bordered join-item input-sm w-full" />
                 <button type="submit" class="btn btn-primary join-item btn-sm">
                     <x-icon name="search" size="4" />
                 </button>
             </form>
             {{-- Add New Button --}}
             <x-button href="{{ route('playlists.create') }}" variant="primary" size="sm" class="flex-shrink-0">
                <x-icon name="plus" size="4" class="mr-1" />
                 Create Playlist
            </x-button>
        </div>
    </div>

    {{-- Main Content Card --}}
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="overflow-x-auto">
                <table class="table table-zebra table-sm w-full">
                    <thead>
                        <tr>
                             {{-- Pass correct props: sortField, direction, field --}}
                            <th><x-sort-link :sortField="$sortField" :direction="$direction" field="title">Title</x-sort-link></th>
                            <th>Description</th>
                             <th><x-sort-link :sortField="$sortField" :direction="$direction" field="tracks_count">Tracks</x-sort-link></th>
                             <th>Genre</th>
                            <th><x-sort-link :sortField="$sortField" :direction="$direction" field="created_at">Created</x-sort-link></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($playlists as $playlist)
                            <tr>
                                <td>
                                     <div class="flex items-center space-x-3">
                                        <div class="avatar">
                                            <div class="mask mask-squircle w-10 h-10">
                                                 {{-- Assume cover_image is a URL --}}
                                                <img src="{{ $playlist->cover_image ?? asset('images/no-playlist-image.png') }}" 
                                                     alt="{{ $playlist->title }}" 
                                                     onerror="this.src='{{ asset('images/no-playlist-image.png') }}'" />
                                            </div>
                                        </div>
                                        <div>
                                            <a href="{{ route('playlists.show', $playlist) }}" class="font-bold hover:text-primary transition duration-150 ease-in-out">
                                                {{ Str::limit($playlist->title, 40) }}
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-sm text-base-content/70">
                                    {{ Str::limit($playlist->description, 50) }}
                                </td>
                                <td>
                                    <span class="badge badge-ghost badge-sm">{{ $playlist->tracks_count }}</span>
                                </td>
                                <td>
                                    @if($playlist->genre)
                                        <a href="{{ route('genres.show', $playlist->genre) }}" class="badge badge-outline badge-sm hover:bg-base-300">{{ $playlist->genre->name }}</a>
                                    @else
                                        <span class="text-base-content/50 text-xs italic">None</span>
                                    @endif
                                </td>
                                <td>
                                    <span title="{{ $playlist->created_at->format('Y-m-d H:i:s') }}">
                                        {{ $playlist->created_at->diffForHumans(null, true) }} ago
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center space-x-1">
                                         <x-tooltip text="View Playlist" position="top">
                                            <x-button href="{{ route('playlists.show', $playlist) }}" variant="ghost" size="xs" icon>
                                                <x-icon name="eye" />
                                            </x-button>
                                        </x-tooltip>
                                        <x-tooltip text="Add/Remove Tracks" position="top">
                                             <x-button href="{{ route('playlists.add-tracks', $playlist) }}" variant="ghost" size="xs" icon>
                                                <x-icon name="plus-circle" size="4" />
                                            </x-button>
                                        </x-tooltip>
                                        <x-tooltip text="Edit Playlist" position="top">
                                            <x-button href="{{ route('playlists.edit', $playlist) }}" variant="ghost" size="xs" icon>
                                                <x-icon name="pencil" />
                                            </x-button>
                                        </x-tooltip>
                                        <x-tooltip text="Delete Playlist" position="top">
                                            <form action="{{ route('playlists.destroy', $playlist) }}" method="POST" onsubmit="return confirm('Delete playlist: {{ addslashes($playlist->title) }}?')" class="inline">
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
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-10">
                                     <div class="text-base-content/70">
                                        <p class="text-lg mb-2">No playlists found.</p>
                                        <x-button href="{{ route('playlists.create') }}" variant="primary" size="sm">
                                            <x-icon name="plus" class="mr-1" /> Create Your First Playlist
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $playlists->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
