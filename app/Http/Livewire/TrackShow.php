<?php

namespace App\Http\Livewire;

use App\Http\Requests\TrackDeleteRequest;
use App\Models\Track;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class TrackShow extends Component
{
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
        try {
            DB::beginTransaction();
            
            $trackName = $this->track->title;
            $trackId = $this->track->id;
            
            $this->track->delete();
            
            Log::info('Track deleted', ['track_id' => $trackId, 'track_name' => $trackName]);
            
            DB::commit();
            
            session()->flash('message', "Track '{$trackName}' has been deleted.");
            $this->redirect(route('tracks.index'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete track', [
                'track_id' => $this->track->id,
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Failed to delete track: ' . $e->getMessage());
        }
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

    public function render()
    {
        return view('livewire.track-show');
    }
} 