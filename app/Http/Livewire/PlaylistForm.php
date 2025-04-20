<?php

namespace App\Http\Livewire;

use App\Http\Requests\PlaylistRequest;
use Livewire\Component;
use App\Models\Playlist;
use App\Models\Genre;
use App\Services\Playlist\PlaylistService;

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

    protected function rules()
    {
        $baseRules = (new PlaylistRequest())->rules();
        
        // Add is_public field that's specific to the Livewire component
        return array_merge($baseRules, [
            'is_public' => 'boolean',
        ]);
    }

    protected $playlistService;

    public function boot(PlaylistService $playlistService)
    {
        $this->playlistService = $playlistService;
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
        $validatedData = $this->validate($this->rules());
        
        try {
            $user = $this->getMockUser();
            $playlist = $this->playlistService->storeFromArray($validatedData, $user);
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'success',
                'message' => "Playlist '{$playlist->title}' created successfully."
            ]);
            
            return redirect()->route('playlists.add-tracks', $playlist);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Failed to store playlist: ' . $e->getMessage()
            ]);
        }
    }

    public function update()
    {
        $validatedData = $this->validate($this->rules());
        
        try {
            $user = $this->getMockUser();
            $this->playlistService->updateFromArray($this->playlist, $validatedData, $user);
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'success',
                'message' => "Playlist '{$this->playlist->title}' updated successfully."
            ]);
            
            return redirect()->route('playlists.show', $this->playlist);
        } catch (\Exception $e) {
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