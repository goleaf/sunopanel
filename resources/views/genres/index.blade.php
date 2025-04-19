<x-app-layout>
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-base-content">Genres</h1>
        <div class="flex space-x-2 w-full sm:w-auto">
             {{-- Search Form --}}
             <form method="GET" action="{{ route('genres.index') }}" class="join flex-grow">
                 <input type="text" name="search" placeholder="Search genres..." value="{{ request('search') }}" class="input input-bordered join-item input-sm w-full" />
                 <button type="submit" class="btn btn-primary join-item btn-sm">
                     <x-icon name="search" size="4" />
                 </button>
             </form>
             {{-- Add New Button --}}
             <x-button href="{{ route('genres.create') }}" variant="primary" size="sm" class="flex-shrink-0">
                <x-icon name="plus" size="4" class="mr-1" />
                 Add Genre
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
                            <th><x-sort-link :sortField="$sortField" :direction="$direction" field="name">Name</x-sort-link></th>
                            <th>Description</th>
                            <th><x-sort-link :sortField="$sortField" :direction="$direction" field="tracks_count">Tracks</x-sort-link></th>
                            <th><x-sort-link :sortField="$sortField" :direction="$direction" field="created_at">Created</x-sort-link></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($genres as $genre)
                            <tr>
                                <td>
                                    <a href="{{ route('genres.show', $genre) }}" class="font-bold hover:text-primary transition duration-150 ease-in-out">
                                        {{ $genre->name }}
                                    </a>
                                </td>
                                <td class="text-sm text-base-content/70">
                                    {{ Str::limit($genre->description, 60) }}
                                </td>
                                <td>
                                    <span class="badge badge-ghost badge-sm">{{ $genre->tracks_count }}</span>
                                </td>
                                <td>
                                    <span title="{{ $genre->created_at->format('Y-m-d H:i:s') }}">
                                        {{ $genre->created_at->diffForHumans(null, true) }} ago
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center space-x-1">
                                        <x-tooltip text="View Genre" position="top">
                                            <x-button href="{{ route('genres.show', $genre) }}" variant="ghost" size="xs" icon>
                                                <x-icon name="eye" />
                                            </x-button>
                                        </x-tooltip>
                                        <x-tooltip text="Edit Genre" position="top">
                                            <x-button href="{{ route('genres.edit', $genre) }}" variant="ghost" size="xs" icon>
                                                <x-icon name="pencil" />
                                            </x-button>
                                        </x-tooltip>
                                        <x-tooltip text="Create Playlist from Genre" position="top">
                                            <x-button href="{{ route('playlists.create-from-genre', $genre) }}" variant="ghost" size="xs" icon>
                                                <x-icon name="collection" />
                                            </x-button>
                                        </x-tooltip>
                                        <x-tooltip text="Delete Genre" position="top">
                                            <form action="{{ route('genres.destroy', $genre) }}" method="POST" onsubmit="return confirm('Delete genre: {{ addslashes($genre->name) }}? This will NOT delete associated tracks.')" class="inline">
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
                                <td colspan="5" class="text-center py-10">
                                    <div class="text-base-content/70">
                                        <p class="text-lg mb-2">No genres found.</p>
                                        <x-button href="{{ route('genres.create') }}" variant="primary" size="sm">
                                            <x-icon name="plus" class="mr-1" /> Add Your First Genre
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
                {{ $genres->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
