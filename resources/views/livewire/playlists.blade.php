<div>
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-base-content">Playlists</h1>
        <div class="flex space-x-2 w-full sm:w-auto">
             {{-- Search Form --}}
             <div class="join flex-grow">
                 <input type="text" wire:model.debounce.300ms="search" placeholder="Search playlists..." class="input input-bordered join-item input-sm w-full" />
                 <button class="btn btn-primary join-item btn-sm">
                     <x-icon name="search" size="4" />
                 </button>
             </div>
             {{-- Add New Button --}}
             <x-button href="{{ route('playlists.create') }}" variant="primary" size="sm" class="flex-shrink-0">
                <x-icon name="plus" size="4" class="mr-1" />
                 Create Playlist
            </x-button>
        </div>
    </div>

    {{-- Genre Filter --}}
    <div class="mb-6">
        <label for="genreFilter" class="block text-sm font-medium text-base-content">Filter by Genre</label>
        <select id="genreFilter" wire:model="genreFilter" class="select select-bordered select-sm w-full max-w-xs">
            <option value="">All Genres</option>
            @foreach($genres as $genre)
                <option value="{{ $genre->id }}">{{ $genre->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Main Content Card --}}
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="overflow-x-auto">
                <table class="table table-zebra table-sm w-full">
                    <thead>
                        <tr>
                            <th><a href="#" wire:click.prevent="sortBy('title')" class="flex items-center space-x-1">
                                Title
                                @if($sortField === 'title')
                                    <span>
                                        @if($direction === 'asc')
                                            <x-icon name="arrow-up" size="4" />
                                        @else
                                            <x-icon name="arrow-down" size="4" />
                                        @endif
                                    </span>
                                @endif
                            </a></th>
                            <th>Description</th>
                            <th><a href="#" wire:click.prevent="sortBy('tracks_count')" class="flex items-center space-x-1">
                                Tracks
                                @if($sortField === 'tracks_count')
                                    <span>
                                        @if($direction === 'asc')
                                            <x-icon name="arrow-up" size="4" />
                                        @else
                                            <x-icon name="arrow-down" size="4" />
                                        @endif
                                    </span>
                                @endif
                            </a></th>
                            <th>Genre</th>
                            <th><a href="#" wire:click.prevent="sortBy('created_at')" class="flex items-center space-x-1">
                                Created
                                @if($sortField === 'created_at')
                                    <span>
                                        @if($direction === 'asc')
                                            <x-icon name="arrow-up" size="4" />
                                        @else
                                            <x-icon name="arrow-down" size="4" />
                                        @endif
                                    </span>
                                @endif
                            </a></th>
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
                                            <button 
                                                wire:click="delete({{ $playlist->id }})" 
                                                wire:confirm="Are you sure you want to delete playlist: {{ $playlist->title }}?"
                                                class="btn btn-ghost btn-xs text-error">
                                                <x-icon name="trash" />
                                            </button>
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

    {{-- JavaScript for handling alerts --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.addEventListener('alert', event => {
                const type = event.detail.type;
                const message = event.detail.message;
                
                // You can use your preferred alert/notification library here
                // This example assumes you have a notification component that accepts type and message
                if (window.showNotification) {
                    window.showNotification(type, message);
                } else {
                    alert(message); // Fallback to basic alert
                }
            });
        });
    </script>
</div> 