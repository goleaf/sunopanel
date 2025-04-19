@extends('layouts.app')

@section('content')
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
        <form action="{{ route('playlists.store-tracks', $playlist) }}" method="POST">
            @csrf
            <h2 class="card-title text-xl mb-4">Select Tracks to Add</h2>

            {{-- Filters --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 bg-base-200/50 rounded-lg">
                {{-- Search Input --}}
                <div class="form-control md:col-span-2">
                    <label class="label pb-1"><span class="label-text">Search Tracks</span></label>
                    <div class="join w-full">
                        <input type="text" id="search" placeholder="Search by title..." class="input input-sm input-bordered join-item w-full" />
                        <button type="button" id="clearSearch" class="btn btn-sm join-item btn-ghost">
                            <x-icon name="x-mark" class="w-4 h-4"/>
                        </button>
                    </div>
                </div>
                {{-- Genre Filter --}}
                <div class="form-control">
                     <label for="genre-filter" class="label pb-1"><span class="label-text">Filter by Genre</span></label>
                     <select id="genre-filter-select" class="select select-sm select-bordered w-full">
                         <option value="" selected>All Genres</option>
                         @foreach($genres as $genre)
                             <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                         @endforeach
                     </select>
                </div>
            </div>

            {{-- Tracks Table --}}
            <div class="overflow-x-auto">
                <table class="table table-zebra table-sm w-full">
                    <thead>
                        <tr>
                            <th>
                                {{-- Checkbox for select/deselect all visible --}}
                                <label>
                                     <input type="checkbox" id="selectAllVisibleCheckbox" class="checkbox checkbox-primary checkbox-xs" title="Select/Deselect All Visible"/>
                                </label>
                            </th>
                            <th>Title</th>
                            <th>Genres</th>
                            <th>Duration</th>
                             {{-- <th>Audio</th> --}}
                        </tr>
                    </thead>
                    <tbody id="tracks-tbody">
                        @foreach($tracks as $track)
                            <tr class="track-row hover" 
                                data-title="{{ strtolower($track->title) }}"
                                data-genres="{{ $track->genres->pluck('id')->join(',') }}">
                                <td>
                                    <label>
                                        <input type="checkbox" name="track_ids[]" value="{{ $track->id }}"
                                            {{ in_array($track->id, $playlistTrackIds ?? []) ? 'disabled checked' : '' }}
                                            class="checkbox checkbox-primary checkbox-xs track-checkbox">
                                    </label>
                                </td>
                                <td>
                                     <div class="flex items-center space-x-3">
                                        <div class="avatar">
                                            <div class="mask mask-squircle w-8 h-8"> {{-- Slightly smaller avatar --}}
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
                                 {{-- <td class="w-1/4">
                                     <x-audio-player :track="$track" :minimal="true" /> 
                                </td> --}} 
                            </tr>
                        @endforeach
                         <tr id="no-results-row" class="hidden">
                             <td colspan="4" class="text-center py-6 text-base-content/70 italic">
                                 No tracks match the current filters.
                             </td>
                         </tr>
                    </tbody>
                </table>
            </div>

             {{-- Selected Count & Actions --}}
            <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-sm font-medium">
                     Selected: <span id="selectedCount" class="badge badge-primary">0</span>
                </div>
                <div class="flex flex-wrap gap-2">
                     <x-button type="button" id="deselectAll" variant="ghost" size="sm">
                        <x-icon name="minus-circle" class="w-4 h-4 mr-1"/> Deselect All
                    </x-button>
                    <x-button type="submit" variant="primary" size="sm">
                        <x-icon name="plus" class="w-4 h-4 mr-1" />
                        Add Selected Tracks (<span id="selectedCountSubmit">0</span>)
                    </x-button>
                </div>
            </div>
        </form>

         {{-- Pagination --}}
         <div class="mt-6">
            {{-- Note: Pagination might not work correctly with JS filtering unless using AJAX/Livewire --}}
            {{ $tracks->links() }} 
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search');
        const clearButton = document.getElementById('clearSearch');
        const genreFilterSelect = document.getElementById('genre-filter-select');
        const trackRows = document.querySelectorAll('#tracks-tbody .track-row');
        const noResultsRow = document.getElementById('no-results-row');
        const selectAllVisibleCheckbox = document.getElementById('selectAllVisibleCheckbox');
        const deselectAllBtn = document.getElementById('deselectAll');
        const selectedCountElements = [document.getElementById('selectedCount'), document.getElementById('selectedCountSubmit')];
        const trackCheckboxes = document.querySelectorAll('.track-checkbox:not([disabled])');
        let visibleRowCount = trackRows.length;

        function updateSelectedCount() {
            const count = document.querySelectorAll('.track-checkbox:checked:not([disabled])').length;
            selectedCountElements.forEach(el => el.textContent = count);
            // Update master checkbox state
            const allVisibleChecked = Array.from(trackCheckboxes).filter(cb => !cb.closest('tr').classList.contains('hidden')).every(cb => cb.checked);
            const anyVisibleChecked = Array.from(trackCheckboxes).filter(cb => !cb.closest('tr').classList.contains('hidden')).some(cb => cb.checked);
            selectAllVisibleCheckbox.checked = allVisibleChecked && visibleRowCount > 0;
            selectAllVisibleCheckbox.indeterminate = !allVisibleChecked && anyVisibleChecked;
            
        }

        function filterTracks() {
            const searchTerm = searchInput.value.toLowerCase();
            const activeGenre = genreFilterSelect.value;
            visibleRowCount = 0;

            trackRows.forEach(row => {
                const title = row.dataset.title;
                const genres = row.dataset.genres.split(',');
                const matchesSearch = title.includes(searchTerm);
                const matchesGenre = activeGenre === '' || genres.includes(activeGenre);
                const isVisible = matchesSearch && matchesGenre;
                row.classList.toggle('hidden', !isVisible);
                if (isVisible) visibleRowCount++;
            });
            noResultsRow.classList.toggle('hidden', visibleRowCount > 0);
            updateSelectedCount(); // Re-evaluate master checkbox
        }

        // Initial setup
        updateSelectedCount();
        filterTracks(); // Apply initial filter state if needed (e.g., from query params)

        // Event Listeners
        searchInput.addEventListener('input', filterTracks);
        clearButton.addEventListener('click', () => { searchInput.value = ''; filterTracks(); });
        genreFilterSelect.addEventListener('change', filterTracks);

        selectAllVisibleCheckbox.addEventListener('change', (e) => {
             trackRows.forEach(row => {
                 if (!row.classList.contains('hidden')) {
                     const checkbox = row.querySelector('.track-checkbox:not([disabled])');
                     if(checkbox) checkbox.checked = e.target.checked;
                 }
             });
             updateSelectedCount();
        });
        
        deselectAllBtn.addEventListener('click', () => {
            trackCheckboxes.forEach(checkbox => checkbox.checked = false);
            updateSelectedCount();
        });

        trackCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });
    });
</script>
@endpush
