<?php

namespace App\Http\Livewire;

use App\Http\Requests\PlaylistRemoveTrackRequest;
use Livewire\Component;
use App\Models\Playlist;
use App\Services\Playlist\PlaylistService;

class PlaylistShow extends Component
{
    public $playlist;
    public $tracks = [];
    public $totalDurationFormatted = '';
    public $genreName = '';
    public $selectedTracks = [];
    public $dragEnabled = false;
    
    protected $playlistService;

    protected function rules()
    {
        return (new PlaylistRemoveTrackRequest())->rules();
    }
    
    protected function messages()
    {
        return (new PlaylistRemoveTrackRequest())->messages();
    }

    public function boot(PlaylistService $playlistService)
    {
        $this->playlistService = $playlistService;
    }

    public function mount(Playlist $playlist)
    {
        try {
            $playlistWithDetails = $this->playlistService->getPlaylistWithTrackDetails($playlist);
            
            $this->playlist = $playlistWithDetails;
            $this->tracks = $playlistWithDetails->tracks;
            $this->totalDurationFormatted = $playlistWithDetails->total_duration_formatted;
            $this->genreName = $playlistWithDetails->genre?->name ?? 'None';
        } catch (\Exception $e) {
            $this->playlist = $playlist;
            $this->tracks = collect();
            session()->flash('error', 'Failed to load playlist details: ' . $e->getMessage());
        }
    }

    public function selectAll()
    {
        $this->selectedTracks = $this->tracks->pluck('id')->toArray();
    }
    
    public function deselectAll()
    {
        $this->selectedTracks = [];
    }
    
    public function removeSelectedTracks()
    {
        // Validate the selected tracks
        $this->validate([
            'selectedTracks.*' => 'exists:tracks,id',
        ]);
        
        if (empty($this->selectedTracks)) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'info',
                'message' => 'No tracks selected for removal.'
            ]);
            return;
        }
        
        try {
            $count = count($this->selectedTracks);
            
            $this->playlistService->removeTracks($this->playlist, $this->selectedTracks);
            
            // Refresh playlist data
            $playlistWithDetails = $this->playlistService->getPlaylistWithTrackDetails($this->playlist->fresh());
            $this->playlist = $playlistWithDetails;
            $this->tracks = $playlistWithDetails->tracks;
            $this->totalDurationFormatted = $playlistWithDetails->total_duration_formatted;
            $this->selectedTracks = []; // Clear selection
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'success',
                'message' => "{$count} track(s) removed from playlist successfully."
            ]);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Error removing tracks: ' . $e->getMessage()
            ]);
        }
    }

    public function removeTrack($trackId)
    {
        // Validate the track ID
        $this->validate([
            'trackId' => 'exists:tracks,id',
        ], [], ['trackId' => $trackId]);
        
        try {
            $playlist = $this->playlist;
            $trackToRemove = $playlist->tracks->firstWhere('id', $trackId);
            
            if (!$trackToRemove) {
                $this->dispatchBrowserEvent('alert', [
                    'type' => 'error',
                    'message' => 'Track not found in this playlist.'
                ]);
                return;
            }
            
            $removed = $this->playlistService->removeTrack($playlist, $trackToRemove);
            
            if ($removed) {
                // Refresh playlist data
                $playlistWithDetails = $this->playlistService->getPlaylistWithTrackDetails($playlist->fresh());
                $this->playlist = $playlistWithDetails;
                $this->tracks = $playlistWithDetails->tracks;
                $this->totalDurationFormatted = $playlistWithDetails->total_duration_formatted;
                
                $this->dispatchBrowserEvent('alert', [
                    'type' => 'success',
                    'message' => "Track removed from playlist successfully."
                ]);
            } else {
                $this->dispatchBrowserEvent('alert', [
                    'type' => 'error',
                    'message' => "Failed to remove track from playlist."
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Error removing track: ' . $e->getMessage()
            ]);
        }
    }

    public function play($trackId)
    {
        $track = $this->tracks->firstWhere('id', $trackId);
        
        if ($track && $track->audio_url) {
            $this->dispatchBrowserEvent('playTrack', [
                'url' => $track->audio_url,
                'title' => $track->title,
                'artist' => $track->artist
            ]);
        } else {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Track not found or audio URL is missing.'
            ]);
        }
    }

    public function toggleDrag()
    {
        $this->dragEnabled = !$this->dragEnabled;
        $this->dispatchBrowserEvent('toggleDragMode', ['enabled' => $this->dragEnabled]);
    }
    
    public function updateTrackOrder(array $orderedTracks)
    {
        // Validate each track ID in the ordered tracks array
        foreach ($orderedTracks as $trackId) {
            $this->validate([
                'trackId' => 'exists:tracks,id', 
            ], [], ['trackId' => $trackId]);
        }
        
        try {
            // Prepare the positions array
            $trackPositions = [];
            foreach ($orderedTracks as $index => $trackId) {
                $trackPositions[] = [
                    'id' => $trackId,
                    'position' => $index + 1, // 1-based position
                ];
            }
            
            $success = $this->playlistService->updateTrackPositions($this->playlist, $trackPositions);
            
            if ($success) {
                // Refresh playlist data
                $playlistWithDetails = $this->playlistService->getPlaylistWithTrackDetails($this->playlist->fresh());
                $this->playlist = $playlistWithDetails;
                $this->tracks = $playlistWithDetails->tracks;
                
                $this->dispatchBrowserEvent('alert', [
                    'type' => 'success',
                    'message' => 'Track order updated successfully.'
                ]);
            } else {
                $this->dispatchBrowserEvent('alert', [
                    'type' => 'error',
                    'message' => 'Failed to update track order.'
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Error updating track order: ' . $e->getMessage()
            ]);
        }
    }

    private function getMockUser()
    {
        return new class {
            public $id = 1;
            public function __get($key) {
                if ($key === 'id') return 1;
                return null;
            }
        };
    }

    public function render()
    {
        return view('livewire.playlist-show');
    }
} 