<?php

namespace App\Livewire;

use App\Models\Genre;
use App\Models\Track;
use Livewire\Component;
use Livewire\WithPagination;

class GenreShow extends Component
{
    use WithPagination;

    public Genre $genre;
    public $sortField = 'title';
    public $direction = 'asc';

    public function mount(Genre $genre)
    {
        $this->genre = $genre;
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

    public function render()
    {
        $tracks = $this->genre->tracks()
            ->orderBy($this->sortField, $this->direction)
            ->paginate(10);

        return view('livewire.genre-show', [
            'tracks' => $tracks,
        ]);
    }
}
