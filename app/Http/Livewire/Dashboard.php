<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\Track;
use App\Models\Playlist;
use App\Models\Genre;

class Dashboard extends Component
{
    public $userCount;
    public $trackCount;
    public $playlistCount;
    public $genreCount;

    public function mount()
    {
        $this->userCount = User::count();
        $this->trackCount = Track::count();
        $this->playlistCount = Playlist::count();
        $this->genreCount = Genre::count();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
} 