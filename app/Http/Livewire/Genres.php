<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Genre;
use App\Services\Genre\GenreService;
use Illuminate\Support\Facades\Auth;

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

    public function create()
    {
        $validatedData = $this->validate([
            'name' => 'required|string|max:255|unique:genres,name',
            'description' => 'nullable|string',
        ]);

        $this->genreService->createGenre($validatedData, Auth::user());

        $this->resetInput();
        $this->loadGenres();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Genre created successfully!']);
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
        $validatedData = $this->validate([
            'name' => 'required|string|max:255|unique:genres,name,' . $this->editingGenreId,
            'description' => 'nullable|string',
        ]);

        $this->genreService->updateGenre($this->editingGenreId, $validatedData, Auth::user());

        $this->resetInput();
        $this->loadGenres();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Genre updated successfully!']);
    }

    public function delete($id)
    {
        $this->genreService->deleteGenre($id, Auth::user());
        $this->loadGenres();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Genre deleted successfully!']);
    }

    public function resetInput()
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