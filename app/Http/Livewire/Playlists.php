<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Playlist;
use App\Models\Genre;
use App\Services\Playlist\PlaylistService;
use App\Services\Logging\LoggingServiceInterface;
use Illuminate\Support\Facades\Auth;

class Playlists extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'created_at';
    public $direction = 'desc';
    public $perPage = 10;
    public $genreFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'direction' => ['except' => 'desc'],
        'genreFilter' => ['except' => ''],
    ];

    protected $playlistService;
    protected $loggingService;

    public function boot(PlaylistService $playlistService, LoggingServiceInterface $loggingService)
    {
        $this->playlistService = $playlistService;
        $this->loggingService = $loggingService;
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->direction = 'asc';
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingGenreFilter()
    {
        $this->resetPage();
    }

    public function delete($id)
    {
        try {
            $playlist = Playlist::findOrFail($id);
            $playlistTitle = $playlist->title;
            
            $this->loggingService->logInfoMessage('Playlist delete initiated from Livewire component', [
                'playlist_id' => $id, 
                'title' => $playlistTitle
            ]);

            $deleted = $this->playlistService->deletePlaylistAndDetachTracks($playlist);

            if ($deleted) {
                $this->loggingService->logInfoMessage('Playlist deleted successfully via service', [
                    'playlist_id' => $id, 
                    'title' => $playlistTitle
                ]);
                $this->dispatchBrowserEvent('alert', [
                    'type' => 'success',
                    'message' => "Playlist '{$playlistTitle}' deleted successfully."
                ]);
            } else {
                $this->loggingService->logErrorMessage('Playlist deletion failed via service (warning)', [
                    'playlist_id' => $id, 
                    'title' => $playlistTitle
                ]);
                $this->dispatchBrowserEvent('alert', [
                    'type' => 'error',
                    'message' => "Failed to delete playlist '{$playlistTitle}'."
                ]);
            }
        } catch (\Exception $e) {
            $this->loggingService->logErrorMessage('Error in Playlists Livewire component delete method', [
                'playlist_id' => $id,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
                'user_id' => Auth::id(),
            ]);
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Failed to delete playlist: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        try {
            $this->loggingService->logInfoMessage('Playlists Livewire component rendered', [
                'search' => $this->search,
                'sortField' => $this->sortField,
                'direction' => $this->direction,
                'genreFilter' => $this->genreFilter,
                'user_id' => Auth::id(),
            ]);

            $playlists = Playlist::with(['genre', 'user'])
                ->withCount('tracks')
                ->when($this->search, function ($query) {
                    return $query->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                })
                ->when($this->genreFilter, function ($query) {
                    return $query->where('genre_id', $this->genreFilter);
                })
                ->orderBy($this->sortField, $this->direction)
                ->paginate($this->perPage);

            $genres = Genre::orderBy('name')->get();

            return view('livewire.playlists', [
                'playlists' => $playlists,
                'genres' => $genres,
            ]);
        } catch (\Exception $e) {
            $this->loggingService->logErrorMessage('Error in Playlists Livewire component render method', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
                'user_id' => Auth::id(),
            ]);

            return view('livewire.playlists', [
                'playlists' => collect(),
                'genres' => collect(),
                'error' => 'An error occurred while loading playlists.'
            ]);
        }
    }
} 