<?php

namespace App\Http\Livewire;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;

#[Lazy]
class SystemStats extends Component
{
    /**
     * Indicates if the component should be rendered on the server.
     *
     * @var bool
     */
    protected bool $shouldRenderOnServer = true;
    
    /**
     * Determines if the component should cache its server-side rendering.
     *
     * @var bool
     */
    protected bool $shouldCacheRender = true;
    
    /**
     * The number of seconds to cache the server-side rendering.
     *
     * @var int
     */
    protected int $renderCacheTtl = 300; // 5 minutes
    
    public int $trackCount = 0;
    public int $genreCount = 0;
    public int $playlistCount = 0;
    public string $totalDuration = '00:00';
    public float $storageUsageMB = 0;
    
    /**
     * The component's initial data for SSR.
     *
     * @return array
     */
    public function boot(): array
    {
        return [
            'placeholder' => 'Loading system statistics...',
        ];
    }
    
    public function mount(): void
    {
        $this->getSystemStats();
    }
    
    private function getSystemStats(): void
    {
        // Use caching for better performance
        $this->trackCount = cache()->remember('system_track_count', 60, function() {
            return Track::count();
        });
        
        $this->genreCount = cache()->remember('system_genre_count', 60, function() {
            return Genre::count();
        });
        
        $this->playlistCount = cache()->remember('system_playlist_count', 60, function() {
            return Playlist::count();
        });
        
        // Calculate total duration in a more efficient way with caching
        $this->calculateTotalDuration();
        
        // Calculate storage usage with caching
        $this->calculateStorageUsage();
    }
    
    private function calculateTotalDuration(): void
    {
        $totalSeconds = cache()->remember('system_total_duration_seconds', 120, function() {
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
            
            return $totalSeconds;
        });

        // Format total seconds to MM:SS format
        $minutes = floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;
        $this->totalDuration = sprintf('%d:%02d', $minutes, $seconds);
    }
    
    private function calculateStorageUsage(): void
    {
        $this->storageUsageMB = cache()->remember('system_storage_usage', 300, function() {
            $usage = 0;
            if (Storage::disk('public')->exists('tracks')) {
                $usage = round(Storage::disk('public')->size('tracks') / (1024 * 1024), 2);
            }
            return $usage;
        });
    }
    
    /**
     * Render the component with server-side rendering
     */
    #[Title('System Statistics')]
    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.system.stats', [
            'trackCount' => $this->trackCount,
            'genreCount' => $this->genreCount,
            'playlistCount' => $this->playlistCount,
            'totalDuration' => $this->totalDuration,
            'storageUsageMB' => $this->storageUsageMB,
        ])->renderOnServer();
    }
} 