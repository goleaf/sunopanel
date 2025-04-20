<?php

namespace App\Http\Livewire;

use App\Http\Requests\PlaylistRemoveTrackRequest;
use Livewire\Component;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlaylistShow extends Component
{
    public $playlist;
    public $tracks = [];
    public $totalDurationFormatted = '';
    public $genreName = '';
    public $selectedTracks = [];
    public $dragEnabled = false;
    
    protected function rules()
    {
        return (new PlaylistRemoveTrackRequest())->rules();
    }
    
    protected function messages()
    {
        return (new PlaylistRemoveTrackRequest())->messages();
    }

    public function mount(Playlist $playlist)
    {
        $this->loadPlaylistDetails($playlist);
    }
    
    protected function loadPlaylistDetails(Playlist $playlist)
    {
        // Load the playlist with its tracks
        $playlist->load(['tracks' => function($query) {
            $query->orderBy('pivot.position', 'asc');
        }, 'genre']);
        
        // Calculate total duration
        $totalSeconds = 0;
        foreach ($playlist->tracks as $track) {
            if (is_numeric($track->duration)) {
                $totalSeconds += (int) $track->duration;
            } elseif (strpos($track->duration, ':') !== false) {
                $parts = explode(':', $track->duration);
                if (count($parts) === 2) {
                    $totalSeconds += (int) $parts[0] * 60 + (int) $parts[1];
                }
            }
        }
        
        // Format total duration
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;
        
        if ($hours > 0) {
            $totalDurationFormatted = sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            $totalDurationFormatted = sprintf('%d:%02d', $minutes, $seconds);
        }
        
        // Set component properties
        $this->playlist = $playlist;
        $this->tracks = $playlist->tracks;
        $this->totalDurationFormatted = $totalDurationFormatted;
        $this->genreName = $playlist->genre?->name ?? 'None';
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
        
        $count = count($this->selectedTracks);
        
        DB::transaction(function () {
            $this->playlist->tracks()->detach($this->selectedTracks);
            
            // After detaching, reorder the remaining tracks to keep positions consistent
            $remainingTracks = $this->playlist->tracks()
                ->orderBy('pivot.position')
                ->get();
            
            $position = 1;
            foreach ($remainingTracks as $track) {
                DB::table('playlist_track')
                    ->where('playlist_id', $this->playlist->id)
                    ->where('track_id', $track->id)
                    ->update(['position' => $position]);
                $position++;
            }
        });
        
        Log::info('Tracks removed from playlist', [
            'playlist_id' => $this->playlist->id,
            'track_ids' => $this->selectedTracks,
            'user_id' => Auth::id(),
            'count' => $count
        ]);
        
        // Refresh playlist data
        $this->loadPlaylistDetails($this->playlist->fresh());
        $this->selectedTracks = []; // Clear selection
        
        $this->dispatchBrowserEvent('alert', [
            'type' => 'success',
            'message' => "{$count} track(s) removed from playlist successfully."
        ]);
    }

    public function removeTrack($trackId)
    {
        // Validate the track ID
        $this->validate([
            'trackId' => 'exists:tracks,id',
        ], [], ['trackId' => $trackId]);
        
        $playlist = $this->playlist;
        $trackToRemove = $playlist->tracks->firstWhere('id', $trackId);
        
        if (!$trackToRemove) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Track not found in this playlist.'
            ]);
            return;
        }
        
        DB::transaction(function () use ($trackId) {
            // Detach the track
            $this->playlist->tracks()->detach($trackId);
            
            // Reorder remaining tracks
            $remainingTracks = $this->playlist->tracks()
                ->orderBy('pivot.position')
                ->get();
            
            $position = 1;
            foreach ($remainingTracks as $track) {
                DB::table('playlist_track')
                    ->where('playlist_id', $this->playlist->id)
                    ->where('track_id', $track->id)
                    ->update(['position' => $position]);
                $position++;
            }
        });
        
        Log::info('Track removed from playlist', [
            'playlist_id' => $this->playlist->id,
            'track_id' => $trackId,
            'user_id' => Auth::id()
        ]);
        
        // Refresh playlist data
        $this->loadPlaylistDetails($this->playlist->fresh());
        
        $this->dispatchBrowserEvent('alert', [
            'type' => 'success',
            'message' => "Track removed from playlist successfully."
        ]);
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
        
        DB::transaction(function () use ($orderedTracks) {
            $playlist = $this->playlist;
            
            // Update each track position
            foreach ($orderedTracks as $index => $trackId) {
                DB::table('playlist_track')
                    ->where('playlist_id', $playlist->id)
                    ->where('track_id', $trackId)
                    ->update(['position' => $index + 1]); // 1-based position
            }
        });
        
        Log::info('Track order updated', [
            'playlist_id' => $this->playlist->id,
            'track_count' => count($orderedTracks),
            'user_id' => Auth::id()
        ]);
        
        // Refresh playlist data
        $this->loadPlaylistDetails($this->playlist->fresh());
        
        $this->dispatchBrowserEvent('alert', [
            'type' => 'success',
            'message' => 'Track order updated successfully.'
        ]);
    }

    public function render()
    {
        return view('livewire.playlist-show');
    }
} 