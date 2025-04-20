<?php

namespace App\Http\Livewire;

use App\Http\Requests\PlaylistListRequest;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Playlist;
use App\Models\Genre;
use App\Services\Playlist\PlaylistService;
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
    
    protected function rules()
    {
        return (new PlaylistListRequest())->rules();
    }
    
    protected function messages()
    {
        return (new PlaylistListRequest())->messages();
    }

    public function boot(PlaylistService $playlistService)
    {
        $this->playlistService = $playlistService;
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
        $this->validate(['playlistId' => 'exists:playlists,id'], [], ['playlistId' => $id]);
        
        $playlist = Playlist::findOrFail($id);
        $playlistTitle = $playlist->title;
        
        $deleted = $this->playlistService->deletePlaylistAndDetachTracks($playlist);

        if ($deleted) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'success',
                'message' => "Playlist '{$playlistTitle}' deleted successfully."
            ]);
        } else {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => "Failed to delete playlist '{$playlistTitle}'."
            ]);
        }
    }

    public function render()
    {
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
    }
} 