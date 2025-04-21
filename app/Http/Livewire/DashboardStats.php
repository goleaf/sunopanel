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

final class DashboardStats extends Component
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
    public array $recentTracks = [];
    public array $popularGenres = [];
    public array $tracksByDay = [];
    
    public function mount(): void
    {
        $this->getStats();
    }
    
    private function getStats(): void
    {
        // Basic counts
        $this->trackCount = Track::count();
        $this->genreCount = Genre::count();
        $this->playlistCount = Playlist::count();
        
        // Total duration calculation
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

        $minutes = floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;
        $this->totalDuration = sprintf('%d:%02d', $minutes, $seconds);
        
        // Storage usage
        $this->storageUsageMB = 0;
        if (Storage::disk('public')->exists('tracks')) {
            $this->storageUsageMB = round(Storage::disk('public')->size('tracks') / (1024 * 1024), 2);
        }
        
        // Recent tracks
        $this->recentTracks = Track::latest()
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
        
        // Popular genres
        $this->popularGenres = Genre::select('genres.id', 'genres.name', DB::raw('COUNT(tracks.id) as track_count'))
            ->leftJoin('genre_track', 'genres.id', '=', 'genre_track.genre_id')
            ->leftJoin('tracks', 'genre_track.track_id', '=', 'tracks.id')
            ->groupBy('genres.id', 'genres.name')
            ->orderByDesc('track_count')
            ->limit(5)
            ->get()
            ->toArray();
        
        // Tracks added by day (last 7 days)
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $tracksByDay = Track::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->toArray();
        
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
     * Render the component
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
        ]);
    }
} 