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
                    <x-button href="{{ route('playlists.add-tracks', $playlist) }}" variant="outline" size="sm">
                        <x-icon name="adjustments" size="4" class="mr-1" />
                        Add/Remove Tracks
                    </x-button>
                    <x-button href="{{ route('playlists.edit', $playlist) }}" variant="outline" size="sm">
                        <x-icon name="pencil" size="4" class="mr-1" />
                        Edit Details
                    </x-button>
                    <button 
                        wire:click="$dispatch('openModal', { component: 'confirm-delete', arguments: { id: {{ $playlist->id }}, type: 'playlist', title: '{{ $playlist->title }}' }})"
                        class="btn btn-outline btn-error btn-sm">
                        <x-icon name="trash" size="4" class="mr-1" />
                        Delete Playlist
                    </button>
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
                                    <dd class="text-base-content">{{ count($tracks) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="font-medium text-base-content/70">Total Duration</dt>
                                    <dd class="text-base-content">{{ $totalDurationFormatted }}</dd> 
                                </div>
                                @if($playlist->genre)
                                    <div class="flex justify-between">
                                        <dt class="font-medium text-base-content/70">Genre</dt>
                                        <dd class="text-base-content">
                                            <a href="{{ route('genres.show', $playlist->genre) }}" class="badge badge-outline badge-sm hover:bg-base-300">{{ $genreName }}</a>
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
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-2">
                                <h2 class="card-title text-xl">Tracks in Playlist ({{ count($tracks) }})</h2>
                                
                                @if(count($tracks) > 0)
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="text-sm font-medium mr-2">
                                        Selected: <span class="badge badge-primary">{{ count($selectedTracks) }}</span>
                                    </div>
                                    <div class="dropdown dropdown-end">
                                        <label tabindex="0" class="btn btn-sm btn-outline">
                                            Actions
                                            <x-icon name="chevron-down" size="4" class="ml-1" />
                                        </label>
                                        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                                            <li><button wire:click="selectAll">
                                                <x-icon name="plus-circle" size="4" />
                                                Select All
                                            </button></li>
                                            <li><button wire:click="deselectAll">
                                                <x-icon name="minus-circle" size="4" />
                                                Deselect All
                                            </button></li>
                                            <li><button wire:click="removeSelectedTracks" wire:confirm="Are you sure you want to remove the selected tracks?">
                                                <x-icon name="trash" size="4" />
                                                Remove Selected
                                            </button></li>
                                        </ul>
                                    </div>
                                    <button type="button" wire:click="toggleDrag" class="btn btn-sm {{ $dragEnabled ? 'btn-primary' : 'btn-outline' }}">
                                        <x-icon name="selector" size="4" class="mr-1" />
                                        {{ $dragEnabled ? 'Exit Reorder Mode' : 'Reorder Tracks' }}
                                    </button>
                                </div>
                                @endif
                            </div>
                            
                            @if(count($tracks) === 0)
                                <div class="text-center py-10 text-base-content/70 italic">
                                    No tracks in this playlist yet. Add some using the button above.
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="table table-zebra table-sm w-full">
                                        <thead>
                                            <tr>
                                                <th class="w-10">
                                                    <input type="checkbox" class="checkbox checkbox-xs"
                                                        @if(count($selectedTracks) === count($tracks)) checked @endif
                                                        @if(count($tracks) === 0) disabled @endif
                                                        wire:click="$toggle('selectedTracks', {{ json_encode($tracks->pluck('id')->toArray()) }})" />
                                                </th>
                                                <th>#</th>
                                                <th>Title</th>
                                                <th>Genres</th>
                                                <th>Duration</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="playlist-tracks" class="{{ $dragEnabled ? 'cursor-move' : '' }}">
                                            @foreach($tracks->sortBy('pivot.position') as $index => $track)
                                                <tr class="{{ $dragEnabled ? 'track-draggable' : '' }}" 
                                                    data-id="{{ $track->id }}"
                                                    data-index="{{ $index }}">
                                                    <td>
                                                        <input type="checkbox" class="checkbox checkbox-xs" 
                                                            wire:model="selectedTracks" value="{{ $track->id }}" />
                                                    </td>
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
                                                                <button wire:click="play({{ $track->id }})" class="btn btn-ghost btn-xs">
                                                                    <x-icon name="play" />
                                                                </button>
                                                            </x-tooltip>
                                                            <x-tooltip text="View Track Details" position="top">
                                                                <x-button href="{{ route('tracks.show', $track) }}" variant="ghost" size="xs" icon>
                                                                    <x-icon name="eye" />
                                                                </x-button>
                                                            </x-tooltip>
                                                            <x-tooltip text="Remove from Playlist" position="top">
                                                                <button 
                                                                    wire:click="removeTrack({{ $track->id }})" 
                                                                    wire:confirm="Are you sure you want to remove this track from the playlist?" 
                                                                    class="btn btn-ghost btn-xs text-error">
                                                                    <x-icon name="trash" size="4" />
                                                                </button>
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

    {{-- JavaScript for handling alerts, track playing, and drag-and-drop --}}
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
            
            window.addEventListener('playTrack', event => {
                const url = event.detail.url;
                const title = event.detail.title;
                const artist = event.detail.artist;
                
                // You can implement your audio player logic here
                if (window.playAudioTrack) {
                    window.playAudioTrack(url, title, artist);
                } else {
                    // Fallback: Create a simple audio element
                    const audio = new Audio(url);
                    audio.play().catch(e => {
                        console.error('Error playing audio:', e);
                        alert('Could not play audio: ' + e.message);
                    });
                }
            });
            
            // Set up Sortable.js instance for drag-and-drop
            let sortable = null;
            
            function initSortable() {
                if (sortable) {
                    sortable.destroy();
                    sortable = null;
                }
                
                const tracksContainer = document.getElementById('playlist-tracks');
                if (!tracksContainer) return;
                
                sortable = new Sortable(tracksContainer, {
                    animation: 150,
                    handle: '.track-draggable',
                    ghostClass: 'opacity-50',
                    chosenClass: 'bg-base-300',
                    disabled: true, // Start with sorting disabled
                    onEnd: function(evt) {
                        // Get the new order of track IDs
                        const trackOrder = Array.from(tracksContainer.querySelectorAll('tr'))
                            .map(row => row.dataset.id);
                        
                        // Send the new order to the Livewire component
                        @this.updateTrackOrder(trackOrder);
                    }
                });
            }
            
            // Initialize Sortable.js
            initSortable();
            
            // Toggle drag mode when requested
            window.addEventListener('toggleDragMode', event => {
                const enabled = event.detail.enabled;
                if (sortable) {
                    sortable.option('disabled', !enabled);
                }
            });
            
            // Re-initialize Sortable when Livewire updates the DOM
            document.addEventListener('livewire:update', initSortable);
        });
    </script>
</x-app-layout> 