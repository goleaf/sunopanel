<?php

namespace App\Http\Livewire;

use App\Models\Track;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class TrackPlay extends Component
{
    /**
     * Since this component returns JSON, we don't need server-side rendering
     * @var bool
     */
    protected bool $shouldRenderOnServer = false;
    
    public $trackId;
    public $track;
    
    public function mount($id)
    {
        $this->trackId = $id;
        $this->track = Track::findOrFail($id);
        
        // Increment play count
        $this->track->increment('play_count');
        
        Log::info("Track played: {$this->track->title}", [
            'track_id' => $this->track->id,
            'user_id' => auth()->id() ?? 'guest',
        ]);
    }
    
    public function render()
    {
        if (empty($this->track->audio_url)) {
            // No audio URL available, return JSON response
            return response()->json([
                'success' => false,
                'message' => 'No audio file associated with this track',
            ]);
        }
        
        // Return audio file URL in JSON format
        return response()->json([
            'success' => true,
            'audio_url' => $this->track->audio_url,
            'track' => [
                'id' => $this->track->id,
                'title' => $this->track->title,
                'artist' => $this->track->artist,
                'duration' => $this->track->duration,
                'image_url' => $this->track->image_url,
            ],
        ]);
    }
} 