<?php

namespace App\Http\Livewire;

use App\Http\Requests\GenreStoreRequest;
use App\Models\Genre;
use Livewire\Component;

class GenreCreate extends Component
{
    public $name = '';
    public $description = '';

    protected function rules()
    {
        return (new GenreStoreRequest())->rules();
    }

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
