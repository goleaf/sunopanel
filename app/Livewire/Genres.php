<?php

namespace App\Livewire;

use App\Models\Genre;
use Livewire\Component;
use Livewire\WithPagination;

class Genres extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'name';
    public $direction = 'asc';

    public function updatingSearch()
    {
        $this->resetPage();
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
        $genres = Genre::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->direction)
            ->paginate(10);

        return view('livewire.genres', [
            'genres' => $genres,
            'sortField' => $this->sortField,
            'direction' => $this->direction,
        ]);
    }
}
