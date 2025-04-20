<?php

namespace App\Http\Livewire;

use App\Http\Requests\PlaylistRequest;
use Livewire\Component;
use App\Models\Playlist;
use App\Models\Genre;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    public function store()
    {
        $validatedData = $this->validate($this->rules());
        
        $playlist = DB::transaction(function () use ($validatedData) {
            // Create the playlist
            $playlist = Playlist::create([
                'title' => $validatedData['title'],
                'description' => $validatedData['description'] ?? null,
                'genre_id' => $validatedData['genre_id'] ?? null,
                'cover_image' => $validatedData['cover_image'] ?? null,
                'is_public' => $validatedData['is_public'] ?? true,
                'user_id' => Auth::id(),
                'slug' => Str::slug($validatedData['title']),
            ]);
            
            Log::info('Playlist created successfully', [
                'playlist_id' => $playlist->id,
                'title' => $playlist->title,
                'user_id' => Auth::id(),
            ]);
            
            return $playlist;
        });
        
        $this->dispatchBrowserEvent('alert', [
            'type' => 'success',
            'message' => "Playlist '{$playlist->title}' created successfully."
        ]);
        
        return redirect()->route('playlists.add-tracks', $playlist);
    }

    public function update()
    {
        $validatedData = $this->validate($this->rules());
        
        DB::transaction(function () use ($validatedData) {
            $this->playlist->update([
                'title' => $validatedData['title'],
                'description' => $validatedData['description'] ?? $this->playlist->description,
                'genre_id' => $validatedData['genre_id'] ?? $this->playlist->genre_id,
                'cover_image' => $validatedData['cover_image'] ?? $this->playlist->cover_image,
                'is_public' => $validatedData['is_public'] ?? $this->playlist->is_public,
                'slug' => Str::slug($validatedData['title']),
            ]);
            
            Log::info('Playlist updated successfully', [
                'playlist_id' => $this->playlist->id,
                'title' => $this->playlist->title,
                'user_id' => Auth::id(),
            ]);
        });
        
        $this->dispatchBrowserEvent('alert', [
            'type' => 'success',
            'message' => "Playlist '{$this->playlist->title}' updated successfully."
        ]);
        
        return redirect()->route('playlists.show', $this->playlist);
    }

    public function render()
    {
        return view('livewire.playlist-form');
    }
} 