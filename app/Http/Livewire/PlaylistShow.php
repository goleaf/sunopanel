<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Playlist;
use App\Services\Playlist\PlaylistService;
use App\Services\Logging\LoggingServiceInterface;
use Illuminate\Support\Facades\Auth;

class PlaylistShow extends Component
{
    public $playlist;
    public $tracks = [];
    public $totalDurationFormatted = '';
    public $genreName = '';
    public $selectedTracks = [];
    public $dragEnabled = false;
    
    protected $playlistService;
    protected $loggingService;

    public function boot(PlaylistService $playlistService, LoggingServiceInterface $loggingService)
    {
        $this->playlistService = $playlistService;
        $this->loggingService = $loggingService;
    }

    public function mount(Playlist $playlist)
    {
        try {
            $this->loggingService->logInfoMessage('PlaylistShow component mounted', [
                'playlist_id' => $playlist->id,
                'title' => $playlist->title,
                'user_id' => Auth::id(),
            ]);
            
            $playlistWithDetails = $this->playlistService->getPlaylistWithTrackDetails($playlist);
            
            $this->playlist = $playlistWithDetails;
            $this->tracks = $playlistWithDetails->tracks;
            $this->totalDurationFormatted = $playlistWithDetails->total_duration_formatted;
            $this->genreName = $playlistWithDetails->genre?->name ?? 'None';
        } catch (\Exception $e) {
            $this->loggingService->logErrorMessage('Error in PlaylistShow component mount method', [
                'playlist_id' => $playlist->id,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
                'user_id' => Auth::id(),
            ]);
            
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
        if (empty($this->selectedTracks)) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'info',
                'message' => 'No tracks selected for removal.'
            ]);
            return;
        }
        
        try {
            $count = count($this->selectedTracks);
            
            $this->loggingService->logInfoMessage('Removing selected tracks from playlist', [
                'playlist_id' => $this->playlist->id,
                'track_count' => $count,
                'track_ids' => $this->selectedTracks,
                'user_id' => Auth::id(),
            ]);
            
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
            $this->loggingService->logErrorMessage('Error in PlaylistShow component removeSelectedTracks method', [
                'playlist_id' => $this->playlist->id,
                'track_ids' => $this->selectedTracks,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
                'user_id' => Auth::id(),
            ]);
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Error removing tracks: ' . $e->getMessage()
            ]);
        }
    }

    public function removeTrack($trackId)
    {
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
            
            $this->loggingService->logInfoMessage('Removing track from playlist', [
                'playlist_id' => $playlist->id,
                'track_id' => $trackId,
                'user_id' => Auth::id(),
            ]);
            
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
            $this->loggingService->logErrorMessage('Error in PlaylistShow component removeTrack method', [
                'playlist_id' => $this->playlist->id,
                'track_id' => $trackId,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
                'user_id' => Auth::id(),
            ]);
            
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
        try {
            // Prepare the positions array
            $trackPositions = [];
            foreach ($orderedTracks as $index => $trackId) {
                $trackPositions[] = [
                    'id' => $trackId,
                    'position' => $index + 1, // 1-based position
                ];
            }
            
            $this->loggingService->logInfoMessage('Updating track positions in playlist', [
                'playlist_id' => $this->playlist->id,
                'track_positions' => $trackPositions,
                'user_id' => Auth::id(),
            ]);
            
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
            $this->loggingService->logErrorMessage('Error in PlaylistShow component updateTrackOrder method', [
                'playlist_id' => $this->playlist->id,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
                'user_id' => Auth::id(),
            ]);
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Error updating track order: ' . $e->getMessage()
            ]);
        }
    }

    private function getMockUser()
    {
        return Auth::user() ?? new class {
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