<?php

namespace App\Http\Livewire;

use App\Models\Track;
use App\Models\Genre;
use Livewire\Component;
use Illuminate\Support\Str;

class TrackCreate extends Component
{
    public $title = '';
    public $audio_url = '';
    public $image_url = '';
    public $duration = '';
    public $genres = '';
    public $selectedGenres = [];
    public $allGenres = [];

    protected $rules = [
        'title' => 'required|string|max:255|unique:tracks',
        'audio_url' => 'required|url',
        'image_url' => 'required|url',
        'duration' => 'nullable|string|max:10',
        'selectedGenres' => 'nullable|array',
        'selectedGenres.*' => 'exists:genres,id',
    ];

    protected $messages = [
        'title.required' => 'The track title is required.',
        'title.unique' => 'A track with this title already exists.',
        'audio_url.required' => 'The audio URL is required.',
        'audio_url.url' => 'The audio URL must be a valid URL.',
        'image_url.required' => 'The image URL is required.',
        'image_url.url' => 'The image URL must be a valid URL.',
        'selectedGenres.*.exists' => 'One or more selected genres do not exist.',
    ];

    public function mount()
    {
        $this->allGenres = Genre::orderBy('name')->get();
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function save()
    {
        $this->validate();

        $track = Track::create([
            'title' => $this->title,
            'audio_url' => $this->audio_url,
            'image_url' => $this->image_url,
            'duration' => $this->duration,
            'unique_id' => Str::uuid(),
        ]);

        if (!empty($this->selectedGenres)) {
            $track->genres()->sync($this->selectedGenres);
        } elseif (!empty($this->genres)) {
            $track->syncGenres($this->genres);
        }

        session()->flash('message', 'Track created successfully.');

        return redirect()->route('tracks.index');
    }

    public function render()
    {
        return view('livewire.track-create');
    }
} 