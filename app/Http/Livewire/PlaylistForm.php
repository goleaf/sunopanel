<?php

namespace App\Http\Livewire;

use App\Http\Requests\PlaylistRequest;
use App\Livewire\BaseComponent;
use App\Models\Playlist;
use App\Models\Genre;
use App\Traits\WithNotifications;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;

class PlaylistForm extends BaseComponent
{
    use WithNotifications;
    
    /**
     * Indicates if the component should be rendered on the server.
     *
     * @var bool
     */
    protected bool $shouldRenderOnServer = true;

    public $playlistId;
    public $title = '';
    public $description = '';
    public $genre_id = '';
    public $cover_image = '';
    public $is_public = true;
    
    public $isEditing = false;
    public $genres = [];
    public $playlist;
    
    /**
     * The component's initial data for SSR.
     *
     * @return array
     */
    public function boot(): array
    {
        return [
            'placeholder' => $this->isEditing ? 'Loading playlist...' : 'Loading playlist form...',
            'genres' => []
        ];
    }

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
        
        $this->notifySuccess("Playlist '{$playlist->title}' created successfully.");
        
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
        
        $this->notifySuccess("Playlist '{$this->playlist->title}' updated successfully.");
        
        return redirect()->route('playlists.show', $this->playlist);
    }

    /**
     * Render the component
     */
    #[Title('Create/Edit Playlist')]
    public function render()
    {
        return $this->renderWithServerRendering(view('livewire.playlist-form', [
            'genres' => $this->genres
        ]));
    }
} 