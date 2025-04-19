<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Track;
use App\Models\Genre;
use App\Services\Track\TrackService;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

class Tracks extends Component
{
    use WithFileUploads;

    public $tracks;
    public $title = '';
    public $artist = '';
    public $album = '';
    public $genre_id;
    public $audio_file;
    public $image_url = '';
    public $editingTrackId = null;
    public $genres;

    protected $trackService;

    public function boot(TrackService $trackService)
    {
        $this->trackService = $trackService;
    }

    public function mount()
    {
        $this->loadTracks();
        $this->genres = Genre::all();
    }

    public function loadTracks()
    {
        $this->tracks = $this->trackService->getAllTracks();
    }

    public function create()
    {
        $validatedData = $this->validate([
            'title' => 'required|string|max:255',
            'artist' => 'required|string|max:255',
            'album' => 'nullable|string|max:255',
            'genre_id' => 'nullable|exists:genres,id',
            'audio_file' => 'required|file|mimes:mp3,wav|max:10240',
            'image_url' => 'nullable|url|max:255',
        ]);

        $this->trackService->createTrack($validatedData, Auth::user(), $this->audio_file);

        $this->resetInput();
        $this->loadTracks();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Track created successfully!']);
    }

    public function edit($id)
    {
        $track = Track::findOrFail($id);
        $this->editingTrackId = $id;
        $this->title = $track->title;
        $this->artist = $track->artist;
        $this->album = $track->album;
        $this->genre_id = $track->genre_id;
        $this->image_url = $track->image_url;
    }

    public function update()
    {
        $validatedData = $this->validate([
            'title' => 'required|string|max:255',
            'artist' => 'required|string|max:255',
            'album' => 'nullable|string|max:255',
            'genre_id' => 'nullable|exists:genres,id',
            'audio_file' => 'nullable|file|mimes:mp3,wav|max:10240',
            'image_url' => 'nullable|url|max:255',
        ]);

        $this->trackService->updateTrack($this->editingTrackId, $validatedData, Auth::user(), $this->audio_file);

        $this->resetInput();
        $this->loadTracks();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Track updated successfully!']);
    }

    public function delete($id)
    {
        $this->trackService->deleteTrack($id, Auth::user());
        $this->loadTracks();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Track deleted successfully!']);
    }

    public function play($id)
    {
        $track = Track::findOrFail($id);
        // Logic to increment play count or log play action can be added here if needed
        $this->dispatchBrowserEvent('playTrack', ['url' => $track->audio_url]);
    }

    public function resetInput()
    {
        $this->title = '';
        $this->artist = '';
        $this->album = '';
        $this->genre_id = null;
        $this->audio_file = null;
        $this->image_url = '';
        $this->editingTrackId = null;
    }

    public function render()
    {
        return view('livewire.tracks');
    }
} 