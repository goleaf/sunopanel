<?php

namespace App\Http\Livewire;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class SystemStats extends Component
{
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
        
        // Calculate total duration
        $totalSeconds = Track::sum('duration');
        $minutes = floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;
        $this->totalDuration = sprintf('%02d:%02d', $minutes, $seconds);
        
        // Calculate storage usage
        $this->storageUsageMB = 0;
        $trackDir = public_path('tracks');
        
        if (file_exists($trackDir)) {
            $size = 0;
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($trackDir)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
            $this->storageUsageMB = round($size / (1024 * 1024), 2);
        }
    }
    
    public function render()
    {
        return view('livewire.system.stats');
    }
} 