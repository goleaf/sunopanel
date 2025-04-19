<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Tracks to Playlist') }}: {{ $playlist->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <x-button href="{{ route('playlists.show', $playlist) }}" color="gray">
                    <x-icon name="arrow-left" class="h-5 w-5 mr-1" />
                    Back to Playlist
                </x-button>
            </div>

            <x-card rounded="lg">
                <form action="{{ route('playlists.store-tracks', $playlist) }}" method="POST">
                    @csrf

                    <div class="mb-6 space-y-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="flex-1">
                                <x-search-input placeholder="Search tracks..." id="search" />
                            </div>
                            <x-button type="button" id="clearSearch" color="gray">
                                Clear
                            </x-button>
                        </div>

                        <div>
                            <x-label for="genre-filter" value="Filter by Genre" />
                            <div class="flex flex-wrap gap-2 mt-2" id="genre-filter">
                                <button type="button" data-genre="" 
                                    class="genre-filter px-3 py-1 text-sm rounded-full border border-gray-300 bg-indigo-100 text-indigo-800 font-medium">
                                    All
                                </button>
                                @foreach($genres as $genre)
                                    <button type="button" data-genre="{{ $genre->id }}" 
                                        class="genre-filter px-3 py-1 text-sm rounded-full border border-gray-300 hover:bg-gray-100">
                                        {{ $genre->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <x-data-table>
                        <x-slot name="header">
                            <x-th>Select</x-th>
                            <x-th>Track</x-th>
                            <x-th>Audio</x-th>
                            <x-th>Genres</x-th>
                        </x-slot>
                        <x-slot name="body">
                            @foreach($availableTracks as $track)
                                <tr class="track-row hover:bg-gray-50" 
                                    data-name="{{ strtolower($track->title) }}"
                                    data-genres="{{ $track->genres->pluck('id')->join(',') }}">
                                    <x-td>
                                        <input type="checkbox" name="track_ids[]" value="{{ $track->id }}"
                                            {{ in_array($track->id, $playlistTrackIds ?? []) ? 'disabled checked' : '' }}
                                            class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    </x-td>
                                    <x-td>
                                        <div class="flex items-center">
                                            <img src="{{ $track->image_url }}" alt="{{ $track->title }}" class="h-10 w-10 object-cover rounded mr-3">
                                            <div class="font-medium text-gray-900">{{ $track->title }}</div>
                                        </div>
                                    </x-td>
                                    <x-td>
                                        <x-audio-player :track="$track" />
                                    </x-td>
                                    <x-td>
                                        @if($track->genres->isNotEmpty())
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($track->genres as $genre)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        {{ $genre->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-500 text-xs">No genres</span>
                                        @endif
                                    </x-td>
                                </tr>
                            @endforeach
                        </x-slot>
                    </x-data-table>

                    <div class="mt-4 mb-6">
                        <span class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full text-sm font-medium">
                            <span id="selectedCount">0</span> tracks selected
                        </span>
                    </div>

                    <div class="flex flex-wrap justify-between gap-4">
                        <div class="flex flex-wrap gap-2">
                            <x-button type="button" id="selectAll" color="gray">
                                Select All Visible
                            </x-button>
                            <x-button type="button" id="deselectAll" color="gray">
                                Deselect All
                            </x-button>
                        </div>
                        <x-button type="submit" color="indigo">
                            <x-icon name="plus" class="h-5 w-5 mr-1" />
                            Add Selected Tracks
                        </x-button>
                    </div>
                </form>

                <div class="mt-6">
                    {{ $availableTracks->links('components.pagination-links') }}
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search');
        const clearButton = document.getElementById('clearSearch');
        const genreFilters = document.querySelectorAll('.genre-filter');
        const trackRows = document.querySelectorAll('.track-row');
        const selectAllBtn = document.getElementById('selectAll');
        const deselectAllBtn = document.getElementById('deselectAll');
        const selectedCountElement = document.getElementById('selectedCount');
        let activeGenre = '';

        // Function to update the selected count
        function updateSelectedCount() {
            const checkedBoxes = document.querySelectorAll('input[name="track_ids[]"]:checked:not([disabled])');
            selectedCountElement.textContent = checkedBoxes.length;
        }

        // Initial count
        updateSelectedCount();

        // Function to filter tracks
        function filterTracks() {
            const searchTerm = searchInput.value.toLowerCase();

            trackRows.forEach(row => {
                const name = row.dataset.name;
                const genres = row.dataset.genres.split(',');

                // Check if it matches both search term and genre filter
                const matchesSearch = name.includes(searchTerm);
                const matchesGenre = activeGenre === '' || genres.includes(activeGenre);

                row.style.display = matchesSearch && matchesGenre ? '' : 'none';
            });
        }

        // Search input event
        searchInput.addEventListener('input', filterTracks);

        // Clear search
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            filterTracks();
        });

        // Genre filter buttons
        genreFilters.forEach(button => {
            button.addEventListener('click', function() {
                // Update active state of buttons
                genreFilters.forEach(btn => {
                    btn.classList.remove('bg-indigo-100', 'text-indigo-800', 'font-medium');
                    btn.classList.add('hover:bg-gray-100');
                });
                this.classList.add('bg-indigo-100', 'text-indigo-800', 'font-medium');
                this.classList.remove('hover:bg-gray-100');

                // Set active genre
                activeGenre = this.dataset.genre;

                // Filter tracks
                filterTracks();
            });
        });

        // Select all visible tracks
        selectAllBtn.addEventListener('click', function() {
            trackRows.forEach(row => {
                if (row.style.display !== 'none') {
                    const checkbox = row.querySelector('input[type="checkbox"]');
                    if (!checkbox.disabled) {
                        checkbox.checked = true;
                    }
                }
            });
            updateSelectedCount();
        });

        // Deselect all tracks
        deselectAllBtn.addEventListener('click', function() {
            document.querySelectorAll('input[name="track_ids[]"]:not([disabled])').forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelectedCount();
        });

        // Listen for checkbox changes to update count
        document.querySelectorAll('input[name="track_ids[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });
    });
</script>
