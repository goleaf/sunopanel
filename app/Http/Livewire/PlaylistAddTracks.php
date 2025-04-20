<?php

namespace App\Http\Livewire;

use App\Http\Requests\PlaylistStoreTracksRequest;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\Genre;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlaylistAddTracks extends Component
{
    use WithPagination;

    public $playlist;
    public $search = '';
    public $genreFilter = '';
    public $selectedTracks = [];
    public $playlistTrackIds = [];
    
    protected $queryString = [
        'search' => ['except' => ''],
        'genreFilter' => ['except' => ''],
    ];

    protected function rules()
    {
        $baseRules = (new PlaylistStoreTracksRequest())->rules();
        
        // Map our component's property names to the request's expected format
        // Also add rules for search and filter parameters
        return array_merge([
            'search' => 'nullable|string|max:255',
            'genreFilter' => 'nullable|exists:genres,id',
            'selectedTracks' => $baseRules['track_ids'],
            'selectedTracks.*' => $baseRules['track_ids.*'],
        ]);
    }

    protected function messages()
    {
        return [
            'genreFilter.exists' => 'The selected genre does not exist.',
            'selectedTracks.required' => 'Please select at least one track.',
            'selectedTracks.*.exists' => 'One or more selected tracks do not exist.',
        ];
    }

    public function mount(Playlist $playlist)
    {
        $this->playlist = $playlist;
        $this->loadPlaylistTrackIds();
    }
    
    private function loadPlaylistTrackIds()
    {
        $this->playlist->load('tracks');
        $this->playlistTrackIds = $this->playlist->tracks->pluck('id')->toArray();
    }

    public function updatingSearch()
    {
        $this->validateOnly('search');
        $this->resetPage();
    }

    public function updatingGenreFilter()
    {
        $this->validateOnly('genreFilter');
        $this->resetPage();
    }

    public function selectAll()
    {
        $availableTracks = $this->getFilteredTracks(false)
            ->whereNotIn('id', $this->playlistTrackIds)
            ->pluck('id')
            ->toArray();
            
        $this->selectedTracks = $availableTracks;
    }

    public function deselectAll()
    {
        $this->selectedTracks = [];
    }

    public function addTracks()
    {
        $this->validate();
        
        if (empty($this->selectedTracks)) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'info',
                'message' => 'No tracks selected for adding.'
            ]);
            return;
        }

        $tracksToAdd = array_diff($this->selectedTracks, $this->playlistTrackIds);
        $count = count($tracksToAdd);
        
        if ($count > 0) {
            DB::transaction(function () use ($tracksToAdd) {
                // Get the next position value
                $maxPosition = DB::table('playlist_track')
                    ->where('playlist_id', $this->playlist->id)
                    ->max('position') ?? 0;
                
                $position = $maxPosition + 1;
                
                // Add each track with its position
                foreach ($tracksToAdd as $trackId) {
                    DB::table('playlist_track')->insert([
                        'playlist_id' => $this->playlist->id,
                        'track_id' => $trackId,
                        'position' => $position++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
            
            Log::info('Tracks added to playlist', [
                'playlist_id' => $this->playlist->id,
                'track_count' => $count,
                'user_id' => Auth::id(),
            ]);
            
            $this->loadPlaylistTrackIds(); // Refresh the list of tracks in playlist
            $this->selectedTracks = []; // Clear selection
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'success',
                'message' => "{$count} track(s) added to playlist '{$this->playlist->title}'."
            ]);
        } else {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'info',
                'message' => 'No new tracks were added. They may already be in the playlist.'
            ]);
        }
    }
    
    private function getFilteredTracks($paginate = true)
    {
        $query = Track::query()
            ->with('genres')
            ->when($this->search, function ($query) {
                return $query->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('artist', 'like', '%' . $this->search . '%')
                    ->orWhere('album', 'like', '%' . $this->search . '%');
            })
            ->when($this->genreFilter, function ($query) {
                return $query->whereHas('genres', function ($q) {
                    $q->where('genres.id', $this->genreFilter);
                });
            })
            ->orderBy('title');
            
        if ($paginate) {
            return $query->paginate(20);
        }
        
        return $query;
    }

    public function addSelectedTracks()
    {
        $this->validate();
        
        $selectedTracks = array_filter($this->selectedTracks, fn($selected) => $selected);
        
        if (empty($selectedTracks)) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'warning',
                'message' => 'No tracks selected. Please select at least one track to add.'
            ]);
            return;
        }
        
        $trackIds = array_keys($selectedTracks);
        
        // Filter out tracks that are already in the playlist
        $newTrackIds = array_diff($trackIds, $this->playlistTrackIds);
        
        if (count($newTrackIds) > 0) {
            DB::transaction(function () use ($newTrackIds) {
                // Get the next position
                $maxPosition = DB::table('playlist_track')
                    ->where('playlist_id', $this->playlist->id)
                    ->max('position') ?? 0;
                
                $position = $maxPosition + 1;
                
                // Add each track with incremented position
                foreach ($newTrackIds as $trackId) {
                    DB::table('playlist_track')->insert([
                        'playlist_id' => $this->playlist->id,
                        'track_id' => $trackId,
                        'position' => $position++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
            
            Log::info('Selected tracks added to playlist', [
                'playlist_id' => $this->playlist->id,
                'track_count' => count($newTrackIds),
                'user_id' => Auth::id(),
            ]);
            
            $this->loadPlaylistTrackIds();
            $this->selectedTracks = [];
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'success', 
                'message' => 'Selected tracks added to playlist!'
            ]);
        } else {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'info', 
                'message' => 'All selected tracks are already in the playlist.'
            ]);
        }
    }

    public function render()
    {
        $tracks = $this->getFilteredTracks();
        $genres = Genre::orderBy('name')->get();
        
        return view('livewire.playlist-add-tracks', [
            'tracks' => $tracks,
            'genres' => $genres,
        ]);
    }
} 