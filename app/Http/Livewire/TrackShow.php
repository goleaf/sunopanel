<?php

namespace App\Http\Livewire;

use App\Http\Requests\TrackDeleteRequest;
use App\Models\Track;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;

class TrackShow extends Component
{
    /**
     * Indicates if the component should be rendered on the server.
     *
     * @var bool
     */
    protected bool $shouldRenderOnServer = true;
    
    public Track $track;
    public bool $showDeleteModal = false;

    protected function rules()
    {
        return (new TrackDeleteRequest())->rules();
    }

    public function mount(Track $track): void
    {
        $this->track = $track;
        $this->track->load(['genres', 'playlists']);
    }

    public function delete(): void
    {
        DB::beginTransaction();
        
        $trackName = $this->track->title;
        $trackId = $this->track->id;
        
        $this->track->delete();
        
        Log::info('Track deleted', ['track_id' => $trackId, 'track_name' => $trackName]);
        
        DB::commit();
        
        session()->flash('message', "Track '{$trackName}' has been deleted.");
        $this->redirect(route('tracks.index'));
    }

    public function toggleDeleteModal(): void
    {
        $this->showDeleteModal = !$this->showDeleteModal;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
    }

    public function playTrack()
    {
        if ($this->track->audio_url) {
            $this->dispatchBrowserEvent('playTrack', [
                'url' => $this->track->audio_url,
                'title' => $this->track->title
            ]);
        } else {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Audio URL is missing.'
            ]);
        }
    }

    /**
     * Render the component
     */
    #[Title('Track Details')]
    public function render()
    {
        return view('livewire.track-show', [
            'track' => $this->track,
        ]);
    }
} 