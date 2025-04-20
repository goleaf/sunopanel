<?php

namespace App\Http\Livewire;

use App\Http\Requests\GenreStoreRequest;
use App\Http\Requests\GenreUpdateRequest;
use Livewire\Component;
use App\Models\Genre;
use App\Services\Genre\GenreService;

class Genres extends Component
{
    public $genres;
    public $name = '';
    public $description = '';
    public $editingGenreId = null;

    protected $genreService;

    public function boot(GenreService $genreService)
    {
        $this->genreService = $genreService;
    }

    public function mount()
    {
        $this->loadGenres();
    }

    public function loadGenres()
    {
        $this->genres = $this->genreService->getAllGenres();
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
        try {
            $user = $this->getMockUser();
            $this->genreService->createGenre($validatedData, $user);
            $this->resetInputFields();
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Genre created successfully!']);
            $this->loadGenres();
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => 'Failed to create genre: ' . $e->getMessage()]);
        }
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
        try {
            $user = $this->getMockUser();
            $this->genreService->updateGenre($this->editingGenreId, $validatedData, $user);
            $this->resetInputFields();
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Genre updated successfully!']);
            $this->loadGenres();
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => 'Failed to update genre: ' . $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        try {
            $user = $this->getMockUser();
            $this->genreService->deleteGenre($id, $user);
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Genre deleted successfully!']);
            $this->loadGenres();
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => 'Failed to delete genre: ' . $e->getMessage()]);
        }
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