<?php

namespace App\Http\Livewire;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Attributes\Title;

class SystemStats extends Component
{
    /**
     * Indicates if the component should be rendered on the server.
     *
     * @var bool
     */
    protected bool $shouldRenderOnServer = true;
    
    public int $trackCount = 0;
    public int $genreCount = 0;
    public int $playlistCount = 0;
    public string $totalDuration = '00:00';
    public float $storageUsageMB = 0;
    
    public function mount(): void
    {
        $this->getSystemStats();
    }
    
    private function getSystemStats(): void
    {
        // Count tracks
        $this->trackCount = Track::count();
        
        // Count genres
        $this->genreCount = Genre::count();
        
        // Count playlists
        $this->playlistCount = Playlist::count();
        
        // Calculate total duration in a more efficient way
        $totalSeconds = 0;

        // Only select the duration column to minimize data transfer
        $tracks = Track::select('duration')
            ->whereNotNull('duration')
            ->where('duration', '!=', '')
            ->get();

        foreach ($tracks as $track) {
            if (is_numeric($track->duration)) {
                $totalSeconds += (int) $track->duration;
            } elseif (strpos($track->duration, ':') !== false) {
                $parts = explode(':', $track->duration);
                if (count($parts) === 2) {
                    $totalSeconds += (int) $parts[0] * 60 + (int) $parts[1];
                }
            }
        }

        // Format total seconds to MM:SS format
        $minutes = floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;
        $this->totalDuration = sprintf('%d:%02d', $minutes, $seconds);
        
        // Calculate storage usage
        $this->storageUsageMB = 0;
        if (Storage::disk('public')->exists('tracks')) {
            $this->storageUsageMB = round(Storage::disk('public')->size('tracks') / (1024 * 1024), 2);
        }
    }
    
    /**
     * Render the component
     */
    #[Title('System Statistics')]
    public function render()
    {
        return view('livewire.system.stats', [
            'trackCount' => $this->trackCount,
            'genreCount' => $this->genreCount,
            'playlistCount' => $this->playlistCount,
            'totalDuration' => $this->totalDuration,
            'storageUsageMB' => $this->storageUsageMB,
        ]);
    }
} 