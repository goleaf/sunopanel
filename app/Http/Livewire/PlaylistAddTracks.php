<?php

namespace App\Http\Livewire;

use App\Http\Requests\PlaylistStoreTracksRequest;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\Genre;
use App\Services\Playlist\PlaylistService;

class PlaylistAddTracks extends Component
{
    use WithPagination;

    public $playlist;
    public $search = '';
    public $genreFilter = '';
    public $selectedTracks = [];
    public $playlistTrackIds = [];
    
    protected $playlistService;
    
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

    public function boot(PlaylistService $playlistService)
    {
        $this->playlistService = $playlistService;
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

        try {
            $count = $this->playlistService->addTracks($this->playlist, $this->selectedTracks);
            
            if ($count > 0) {
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
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'An error occurred while adding tracks: ' . $e->getMessage()
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

    private function getMockUser()
    {
        return new class {
            public $id = 1;
            public function __get($key) {
                if ($key === 'id') return 1;
                return null;
            }
        };
    }

    public function addSelectedTracks()
    {
        $this->validate();
        
        try {
            $user = $this->getMockUser();
            $selectedTracks = array_filter($this->selectedTracks, fn($selected) => $selected);
            
            if (empty($selectedTracks)) {
                $this->dispatchBrowserEvent('alert', [
                    'type' => 'warning',
                    'message' => 'No tracks selected. Please select at least one track to add.'
                ]);
                return;
            }
            
            $this->playlistService->addTracksByIds($this->playlist, array_keys($selectedTracks), $user);
            
            $this->loadPlaylistTrackIds();
            $this->selectedTracks = [];
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'success', 
                'message' => 'Selected tracks added to playlist!'
            ]);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Failed to add tracks: ' . $e->getMessage()
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