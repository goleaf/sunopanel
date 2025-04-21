<?php

namespace App\Http\Livewire;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Lazy;

#[Lazy]
final class DashboardStats extends Component
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
     * Dashboard stats don't change often, so we can cache for longer.
     *
     * @var int
     */
    protected int $renderCacheTtl = 300; // 5 minutes
    
    /**
     * Properties that should be cached during SSR.
     *
     * @var array
     */
    protected array $serverRenderCache = [
        'trackCount',
        'genreCount',
        'playlistCount',
        'totalDuration',
        'storageUsageMB',
        'recentTracks',
        'popularGenres',
        'tracksByDay',
    ];
    
    public int $trackCount = 0;
    public int $genreCount = 0;
    public int $playlistCount = 0;
    public string $totalDuration = '00:00';
    public float $storageUsageMB = 0;
    public array $recentTracks = [];
    public array $popularGenres = [];
    public array $tracksByDay = [];
    
    public function boot(): array
    {
        return [
            'placeholder' => 'Loading dashboard statistics...',
        ];
    }
    
    public function mount(): void
    {
        $this->getStats();
    }
    
    private function getStats(): void
    {
        // Basic counts - Use caching for better performance
        $this->trackCount = cache()->remember('dashboard_track_count', 60, function() {
            return Track::count();
        });
        
        $this->genreCount = cache()->remember('dashboard_genre_count', 60, function() {
            return Genre::count();
        });
        
        $this->playlistCount = cache()->remember('dashboard_playlist_count', 60, function() {
            return Playlist::count();
        });
        
        // Total duration calculation
        $this->calculateTotalDuration();
        
        // Storage usage
        $this->calculateStorageUsage();
        
        // Recent tracks
        $this->getRecentTracks();
        
        // Popular genres
        $this->getPopularGenres();
        
        // Tracks added by day (last 7 days)
        $this->getTracksByDay();
    }
    
    private function calculateTotalDuration(): void
    {
        $totalSeconds = cache()->remember('dashboard_total_duration_seconds', 60, function() {
            $totalSeconds = 0;
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

        $minutes = floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;
        $this->totalDuration = sprintf('%d:%02d', $minutes, $seconds);
    }
    
    private function calculateStorageUsage(): void
    {
        $this->storageUsageMB = cache()->remember('dashboard_storage_usage', 300, function() {
            $usage = 0;
            if (Storage::disk('public')->exists('tracks')) {
                $usage = round(Storage::disk('public')->size('tracks') / (1024 * 1024), 2);
            }
            return $usage;
        });
    }
    
    private function getRecentTracks(): void
    {
        $this->recentTracks = cache()->remember('dashboard_recent_tracks', 30, function() {
            return Track::latest()
                ->select('id', 'title', 'artist', 'created_at')
                ->limit(5)
                ->get()
                ->map(function ($track) {
                    return [
                        'id' => $track->id,
                        'title' => $track->title,
                        'artist' => $track->artist,
                        'created_at' => Carbon::parse($track->created_at)->diffForHumans()
                    ];
                })
                ->toArray();
        });
    }
    
    private function getPopularGenres(): void
    {
        $this->popularGenres = cache()->remember('dashboard_popular_genres', 60, function() {
            return Genre::select('genres.id', 'genres.name', DB::raw('COUNT(tracks.id) as track_count'))
                ->leftJoin('genre_track', 'genres.id', '=', 'genre_track.genre_id')
                ->leftJoin('tracks', 'genre_track.track_id', '=', 'tracks.id')
                ->groupBy('genres.id', 'genres.name')
                ->orderByDesc('track_count')
                ->limit(5)
                ->get()
                ->toArray();
        });
    }
    
    private function getTracksByDay(): void
    {
        $tracksByDay = cache()->remember('dashboard_tracks_by_day', 60, function() {
            $startDate = Carbon::now()->subDays(6)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
            
            return Track::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date')
                ->toArray();
        });
        
        $this->tracksByDay = [];
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $this->tracksByDay[$date] = [
                'label' => Carbon::now()->subDays($i)->format('D'),
                'count' => $tracksByDay[$date]['count'] ?? 0
            ];
        }
        $this->tracksByDay = array_reverse($this->tracksByDay);
    }
    
    /**
     * Render the component with server-side rendering
     */
    #[Title('Dashboard Statistics')]
    public function render()
    {
        return view('livewire.dashboard-stats', [
            'trackCount' => $this->trackCount,
            'genreCount' => $this->genreCount,
            'playlistCount' => $this->playlistCount,
            'totalDuration' => $this->totalDuration,
            'storageUsageMB' => $this->storageUsageMB,
            'recentTracks' => $this->recentTracks,
            'popularGenres' => $this->popularGenres,
            'tracksByDay' => $this->tracksByDay,
        ])->renderOnServer();
    }
} 