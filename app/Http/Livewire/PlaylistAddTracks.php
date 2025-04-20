<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\Genre;
use App\Services\Playlist\PlaylistService;
use App\Services\Logging\LoggingServiceInterface;

class PlaylistAddTracks extends Component
{
    use WithPagination;

    public $playlist;
    public $search = '';
    public $genreFilter = '';
    public $selectedTracks = [];
    public $playlistTrackIds = [];
    
    protected $playlistService;
    protected $loggingService;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'genreFilter' => ['except' => ''],
    ];

    public function boot(PlaylistService $playlistService, LoggingServiceInterface $loggingService)
    {
        $this->playlistService = $playlistService;
        $this->loggingService = $loggingService;
    }

    public function mount(Playlist $playlist)
    {
        $this->playlist = $playlist;
        $this->loadPlaylistTrackIds();
        
        $this->loggingService->logInfoMessage('PlaylistAddTracks component mounted', [
            'playlist_id' => $playlist->id,
            'title' => $playlist->title,
        ]);
    }
    
    private function loadPlaylistTrackIds()
    {
        $this->playlist->load('tracks');
        $this->playlistTrackIds = $this->playlist->tracks->pluck('id')->toArray();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingGenreFilter()
    {
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
        if (empty($this->selectedTracks)) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'info',
                'message' => 'No tracks selected for adding.'
            ]);
            return;
        }

        try {
            $this->loggingService->logInfoMessage('Adding tracks to playlist from Livewire component', [
                'playlist_id' => $this->playlist->id,
                'track_count' => count($this->selectedTracks),
                'track_ids' => $this->selectedTracks,
            ]);
            
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
            $this->loggingService->logErrorMessage('Error in PlaylistAddTracks component addTracks method', [
                'playlist_id' => $this->playlist->id,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);
            
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
            
            $this->loggingService->logInfoMessage('PlaylistAddTracks: Adding tracks to playlist', [
                'playlist_id' => $this->playlist->id,
                'track_ids' => array_keys($selectedTracks),
            ]);
            
            $this->playlistService->addTracksByIds($this->playlist, array_keys($selectedTracks), $user);
            
            $this->loadTrackData();
            $this->selectedTracks = [];
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'success', 
                'message' => 'Selected tracks added to playlist!'
            ]);
        } catch (\Exception $e) {
            $this->loggingService->logErrorMessage('Error in PlaylistAddTracks addSelectedTracks method', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
                'playlist_id' => $this->playlist->id,
            ]);
            
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