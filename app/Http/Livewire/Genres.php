<?php

namespace App\Http\Livewire;

use App\Http\Requests\GenreStoreRequest;
use App\Http\Requests\GenreUpdateRequest;
use Livewire\Component;
use App\Models\Genre;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Genres extends Component
{
    public $genres;
    public $name = '';
    public $description = '';
    public $editingGenreId = null;

    public function mount()
    {
        $this->loadGenres();
    }

    public function loadGenres()
    {
        $this->genres = Genre::orderBy('name')->get();
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

    public function create()
    {
        $validatedData = $this->validate((new GenreStoreRequest())->rules());
        $user = $this->getMockUser();
        
        // Generate a slug from the name
        $slug = Str::slug($validatedData['name']);
        
        $genre = Genre::create([
            'name' => $validatedData['name'],
            'slug' => $slug,
            'description' => $validatedData['description'] ?? null,
        ]);

        Log::info('Genre created successfully', [
            'genre_id' => $genre->id,
            'name' => $genre->name,
            'slug' => $genre->slug,
            'user_id' => $user->id,
        ]);
        
        $this->resetInputFields();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Genre created successfully!']);
        $this->loadGenres();
    }

    public function edit($id)
    {
        $genre = Genre::findOrFail($id);
        $this->editingGenreId = $id;
        $this->name = $genre->name;
        $this->description = $genre->description;
    }

    public function update()
    {
        // Get the rules but override unique constraint for the current genre
        $rules = (new GenreUpdateRequest())->rules();
        if (isset($rules['name'])) {
            $uniqueRule = array_search('unique:genres,name', $rules['name']);
            if ($uniqueRule !== false) {
                $rules['name'][$uniqueRule] = 'unique:genres,name,' . $this->editingGenreId;
            }
        }
        
        $validatedData = $this->validate($rules);
        $user = $this->getMockUser();
        $genre = Genre::findOrFail($this->editingGenreId);
        
        // Update slug if name changes
        $slug = $genre->slug;
        if ($validatedData['name'] !== $genre->name) {
            $slug = Str::slug($validatedData['name']);
        }
        
        $genre->update([
            'name' => $validatedData['name'],
            'slug' => $slug,
            'description' => $validatedData['description'] ?? $genre->description,
        ]);

        Log::info('Genre updated successfully', [
            'genre_id' => $genre->id,
            'name' => $genre->name,
            'slug' => $genre->slug,
            'user_id' => $user->id,
        ]);
        
        $this->resetInputFields();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Genre updated successfully!']);
        $this->loadGenres();
    }

    public function delete($id)
    {
        $user = $this->getMockUser();
        $genre = Genre::findOrFail($id);
        
        // Check if genre has associated tracks
        $tracksCount = $genre->tracks()->count();
        
        if ($tracksCount > 0) {
            Log::warning('Cannot delete genre with associated tracks', [
                'genre_id' => $genre->id,
                'name' => $genre->name,
                'tracks_count' => $tracksCount,
                'user_id' => $user->id,
            ]);
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error', 
                'message' => 'Cannot delete genre with associated tracks. Remove the tracks first or reassign them to another genre.'
            ]);
            return;
        }
        
        DB::transaction(function() use ($genre) {
            // Detach from any playlists
            $genre->tracks()->detach();
            $genre->delete();
        });
        
        Log::info('Genre deleted successfully', [
            'genre_id' => $id,
            'name' => $genre->name,
            'user_id' => $user->id,
        ]);
        
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Genre deleted successfully!']);
        $this->loadGenres();
    }

    public function resetInputFields()
    {
        $this->name = '';
        $this->description = '';
        $this->editingGenreId = null;
    }

    public function render()
    {
        return view('livewire.genres');
    }
} 