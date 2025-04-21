<x-app-layout>
    {{-- Page Header --}}
    <div class="flex justify-between items-center mb-6">
        <div class="breadcrumbs text-sm">
            <ul>
                <li><a href="{{ route('playlists.index') }}">Playlists</a></li>
                <li><a href="{{ route('playlists.show', $playlist) }}" class="truncate" title="{{ $playlist->title }}">{{ Str::limit($playlist->title, 30) }}</a></li>
                <li>Add Tracks</li>
            </ul>
        </div>
        <x-button href="{{ route('playlists.show', $playlist) }}" variant="outline" size="sm">
            <x-icon name="arrow-sm-left" size="4" class="mr-1" />
            Back to Playlist
        </x-button>
    </div>

    {{-- Main Content Card --}}
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title text-xl mb-4">Select Tracks to Add</h2>

            {{-- Filters --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 bg-base-200/50 rounded-lg">
                {{-- Search Input --}}
                <div class="form-control md:col-span-2">
                    <label class="label pb-1"><span class="label-text">Search Tracks</span></label>
                    <div class="join w-full">
                        <input type="text" wire:model.debounce.300ms="search" placeholder="Search by title..." class="input input-sm input-bordered join-item w-full" />
                        <button type="button" wire:click="$set('search', '')" class="btn btn-sm join-item btn-ghost">
                            <x-icon name="x-mark" class="w-4 h-4"/>
                        </button>
                    </div>
                </div>
                {{-- Genre Filter --}}
                <div class="form-control">
                     <label for="genreFilter" class="label pb-1"><span class="label-text">Filter by Genre</span></label>
                     <select id="genreFilter" wire:model="genreFilter" class="select select-sm select-bordered w-full">
                         <option value="">All Genres</option>
                         @foreach($genres as $genre)
                             <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                         @endforeach
                     </select>
                </div>
            </div>

            {{-- Selected Count & Actions --}}
            <div class="mb-4 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-sm font-medium">
                     Selected: <span class="badge badge-primary">{{ count($selectedTracks) }}</span>
                </div>
                <div class="flex flex-wrap gap-2">
                     <x-button type="button" wire:click="selectAll" variant="ghost" size="sm">
                        <x-icon name="plus-circle" class="w-4 h-4 mr-1"/> Select All
                    </x-button>
                    <x-button type="button" wire:click="deselectAll" variant="ghost" size="sm">
                        <x-icon name="minus-circle" class="w-4 h-4 mr-1"/> Deselect All
                    </x-button>
                    <x-button type="button" wire:click="addTracks" variant="primary" size="sm">
                        <x-icon name="plus" class="w-4 h-4 mr-1" />
                        Add Selected Tracks ({{ count($selectedTracks) }})
                    </x-button>
                </div>
            </div>

            {{-- Tracks Table --}}
            <div class="overflow-x-auto">
                <table class="table table-zebra table-sm w-full">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Title</th>
                            <th>Artist</th>
                            <th>Genres</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tracks as $track)
                            <tr @if(in_array($track->id, $playlistTrackIds)) class="opacity-50" @endif>
                                <td>
                                    @if(in_array($track->id, $playlistTrackIds))
                                        <div class="badge badge-success badge-sm">Already Added</div>
                                    @else
                                        <input type="checkbox" wire:model="selectedTracks" value="{{ $track->id }}"
                                            class="checkbox checkbox-primary checkbox-xs">
                                    @endif
                                </td>
                                <td>
                                     <div class="flex items-center space-x-3">
                                        <div class="avatar">
                                            <div class="mask mask-squircle w-8 h-8">
                                                <img src="{{ $track->image_url ?? asset('images/no-image.jpg') }}" 
                                                     alt="{{ $track->title }}" 
                                                     onerror="this.src='{{ asset('images/no-image.jpg') }}'" />
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-medium">{{ Str::limit($track->title, 50) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $track->artist }}</td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($track->genres->take(2) as $genre)
                                            <div class="badge badge-ghost badge-xs">{{ $genre->name }}</div>
                                        @endforeach
                                        @if($track->genres->count() > 2)
                                            <div class="badge badge-ghost badge-xs">...</div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    {{ formatDuration($track->duration_seconds ?: $track->duration) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-6 text-base-content/70 italic">
                                    No tracks match your search criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $tracks->links() }}
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
                if (window.showNotification) {
                    window.showNotification(type, message);
                } else {
                    alert(message); // Fallback to basic alert
                }
            });
        });
    </script>
</x-app-layout> 