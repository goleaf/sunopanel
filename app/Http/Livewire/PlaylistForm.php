<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Playlist;
use App\Models\Genre;
use App\Services\Playlist\PlaylistService;
use App\Services\Logging\LoggingServiceInterface;

class PlaylistForm extends Component
{
    public $playlistId;
    public $title = '';
    public $description = '';
    public $genre_id = '';
    public $cover_image = '';
    public $is_public = true;
    
    public $isEditing = false;
    public $genres = [];
    public $playlist;

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
        'genre_id' => 'nullable|exists:genres,id',
        'cover_image' => 'nullable|url|max:255',
        'is_public' => 'boolean',
    ];

    protected $playlistService;
    protected $loggingService;

    public function boot(PlaylistService $playlistService, LoggingServiceInterface $loggingService)
    {
        $this->playlistService = $playlistService;
        $this->loggingService = $loggingService;
    }

    public function mount($playlist = null)
    {
        $this->genres = Genre::orderBy('name')->get();
        
        if ($playlist) {
            $this->playlist = $playlist;
            $this->playlistId = $playlist->id;
            $this->title = $playlist->title;
            $this->description = $playlist->description;
            $this->genre_id = $playlist->genre_id;
            $this->cover_image = $playlist->cover_image;
            $this->is_public = $playlist->is_public;
            $this->isEditing = true;
            
            $this->loggingService->logInfoMessage('PlaylistForm loaded for editing', [
                'playlist_id' => $this->playlistId,
                'title' => $this->title,
            ]);
        } else {
            $this->loggingService->logInfoMessage('PlaylistForm loaded for creation');
        }
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

    public function store()
    {
        $validatedData = $this->validate();
        
        try {
            $this->loggingService->logInfoMessage('PlaylistForm: Storing new playlist', [
                'data' => $validatedData,
            ]);
            
            $user = $this->getMockUser();
            $playlist = $this->playlistService->storeFromArray($validatedData, $user);
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'success',
                'message' => "Playlist '{$playlist->title}' created successfully."
            ]);
            
            return redirect()->route('playlists.add-tracks', $playlist);
        } catch (\Exception $e) {
            $this->loggingService->logErrorMessage('Error in PlaylistForm store method', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Failed to store playlist: ' . $e->getMessage()
            ]);
        }
    }

    public function update()
    {
        $validatedData = $this->validate();
        
        try {
            $this->loggingService->logInfoMessage('PlaylistForm: Updating playlist', [
                'playlist_id' => $this->playlist->id,
                'data' => $validatedData,
            ]);
            
            $user = $this->getMockUser();
            $this->playlistService->updateFromArray($this->playlist, $validatedData, $user);
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'success',
                'message' => "Playlist '{$this->playlist->title}' updated successfully."
            ]);
            
            return redirect()->route('playlists.show', $this->playlist);
        } catch (\Exception $e) {
            $this->loggingService->logErrorMessage('Error in PlaylistForm update method', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
                'playlist_id' => $this->playlist->id,
            ]);
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Failed to update playlist: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.playlist-form');
    }
} 