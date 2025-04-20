<?php

namespace App\Http\Livewire;

use App\Models\Genre;
use Livewire\Component;

class GenreCreate extends Component
{
    public $name = '';
    public $description = '';

    protected $rules = [
        'name' => 'required|string|max:255|unique:genres,name',
        'description' => 'nullable|string',
    ];

    public function save()
    {
        $this->validate();

        Genre::create([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        session()->flash('message', 'Genre created successfully.');

        return redirect()->route('genres.index');
    }

    public function render()
    {
        return view('livewire.genre-create');
    }
}
