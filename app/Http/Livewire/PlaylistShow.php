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

    public function render()
    {
        return view('livewire.playlist-show');
    }
} 