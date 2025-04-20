<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Playlist;
use App\Models\Genre;
use App\Services\Playlist\PlaylistService;
use App\Services\Logging\LoggingServiceInterface;
use Illuminate\Support\Facades\Auth;

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
                'user_id' => Auth::id(),
            ]);
        } else {
            $this->loggingService->logInfoMessage('PlaylistForm loaded for creation', [
                'user_id' => Auth::id(),
            ]);
        }
    }

    public function save()
    {
        $validatedData = $this->validate();
        
        try {
            if ($this->isEditing) {
                $playlist = Playlist::findOrFail($this->playlistId);
                $this->loggingService->logInfoMessage('Updating playlist', [
                    'playlist_id' => $this->playlistId,
                    'title' => $this->title,
                    'user_id' => Auth::id(),
                ]);
                
                $playlist = $this->playlistService->updateFromArray($validatedData, $playlist);
                
                $this->dispatchBrowserEvent('alert', [
                    'type' => 'success',
                    'message' => "Playlist '{$playlist->title}' updated successfully."
                ]);
                
                return redirect()->route('playlists.show', $playlist);
            } else {
                $this->loggingService->logInfoMessage('Creating new playlist', [
                    'title' => $this->title,
                    'user_id' => Auth::id(),
                ]);
                
                $playlist = $this->playlistService->storeFromArray($validatedData, Auth::user());
                
                $this->dispatchBrowserEvent('alert', [
                    'type' => 'success',
                    'message' => "Playlist '{$playlist->title}' created successfully."
                ]);
                
                return redirect()->route('playlists.add-tracks', $playlist);
            }
        } catch (\Exception $e) {
            $this->loggingService->logErrorMessage('Error in PlaylistForm save method', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
                'is_editing' => $this->isEditing,
                'playlist_id' => $this->playlistId,
                'user_id' => Auth::id(),
            ]);
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Failed to save playlist: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.playlist-form');
    }
} 