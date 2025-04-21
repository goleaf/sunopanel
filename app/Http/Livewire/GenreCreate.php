<?php

namespace App\Http\Livewire;

use App\Http\Requests\GenreStoreRequest;
use App\Models\Genre;
use Livewire\Component;
use Livewire\Attributes\Title;

class GenreCreate extends Component
{
    /**
     * Indicates if the component should be rendered on the server.
     *
     * @var bool
     */
    protected bool $shouldRenderOnServer = true;
    
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

    /**
     * Render the component
     */
    #[Title('Create New Genre')]
    public function render()
    {
        return view('livewire.genre-create');
    }
}
