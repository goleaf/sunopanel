<div>
    <x-heading :title="'Manage Tracks'" :breadcrumbs="['Tracks']">
        <x-slot name="actions">
            <a href="{{ route('tracks.create') }}" class="btn btn-primary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add Track
            </a>
            <a href="{{ route('tracks.bulk-upload') }}" class="btn btn-secondary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                Bulk Upload
            </a>
        </x-slot>
    </x-heading>

    @if (session()->has('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-error mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex flex-col md:flex-row gap-4 mb-6">
                <div class="form-control w-full md:w-1/3">
                    <label class="label">
                        <span class="label-text">Search</span>
                    </label>
                    <input type="text" wire:model.debounce.300ms="search" placeholder="Search by title, artist, or album..." class="input input-bordered" />
                </div>
                
                <div class="form-control w-full md:w-1/3">
                    <label class="label">
                        <span class="label-text">Filter by Genre</span>
                    </label>
                    <select wire:model="genreFilter" class="select select-bordered">
                        <option value="">All Genres</option>
                        @foreach($genres as $genre)
                            <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th class="w-12"></th>
                            <th>
                                <a href="#" wire:click.prevent="sortBy('title')" class="flex items-center">
                                    Title
                                    @if ($sortField === 'title')
                                        <span class="ml-1">
                                            @if ($direction === 'asc')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        </span>
                                    @endif
                                </a>
                            </th>
                            <th>
                                <a href="#" wire:click.prevent="sortBy('artist')" class="flex items-center">
                                    Artist
                                    @if ($sortField === 'artist')
                                        <span class="ml-1">
                                            @if ($direction === 'asc')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        </span>
                                    @endif
                                </a>
                            </th>
                            <th>Duration</th>
                            <th>Genres</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tracks as $track)
                            <tr>
                                <td>
                                    @if($track->image_url)
                                        <img src="{{ $track->image_url }}" alt="{{ $track->title }}" class="w-10 h-10 object-cover rounded">
                                    @else
                                        <div class="w-10 h-10 bg-primary flex items-center justify-center rounded text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                            </svg>
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $track->title }}</td>
                                <td>{{ $track->artist ?: '-' }}</td>
                                <td>{{ $track->duration ?: '-' }}</td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($track->genres as $genre)
                                            <span class="badge badge-sm">{{ $genre->name }}</span>
                                        @empty
                                            <span class="text-sm text-gray-500">-</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="flex justify-end gap-2">
                                        @if($track->audio_url)
                                            <button class="btn btn-circle btn-ghost btn-xs" 
                                                    onclick="playTrack('{{ $track->id }}', '{{ $track->title }}', '{{ $track->audio_url }}')">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                        @endif
                                        <a href="{{ route('tracks.edit', $track->id) }}" class="btn btn-circle btn-ghost btn-xs">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </a>
                                        <button wire:click="confirmDelete({{ $track->id }})" class="btn btn-circle btn-ghost btn-xs">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                        </svg>
                                        <p class="text-gray-500">No tracks found</p>
                                        <a href="{{ route('tracks.create') }}" class="btn btn-primary btn-sm mt-3">Add Your First Track</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $tracks->links() }}
            </div>
        </div>
    </div>

    @if($showDeleteModal)
        <div class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg">Confirm Delete</h3>
                <p class="py-4">Are you sure you want to delete this track? This action cannot be undone.</p>
                <div class="modal-action">
                    <button wire:click="cancelDelete" class="btn">Cancel</button>
                    <button wire:click="deleteTrack" class="btn btn-error">Delete</button>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Audio Player Modal -->
    <div id="audioPlayerModal" class="modal">
        <div class="modal-box">
            <h3 id="trackTitle" class="font-bold text-lg mb-4">Track Title</h3>
            <audio id="audioPlayer" controls class="w-full">
                <source src="" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
            <div class="modal-action">
                <button onclick="closeAudioPlayer()" class="btn">Close</button>
            </div>
        </div>
    </div>
    
    <script>
        function playTrack(id, title, url) {
            document.getElementById('trackTitle').textContent = title;
            const player = document.getElementById('audioPlayer');
            player.src = url;
            document.getElementById('audioPlayerModal').classList.add('modal-open');
            player.play();
        }
        
        function closeAudioPlayer() {
            const player = document.getElementById('audioPlayer');
            player.pause();
            document.getElementById('audioPlayerModal').classList.remove('modal-open');
        }
    </script>
</div> 