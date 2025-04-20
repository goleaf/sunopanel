<?php

namespace App\Http\Livewire;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class SystemStats extends Component
{
    public $stats;

    public function mount()
    {
        $this->stats = $this->getSystemStats();
    }

    /**
     * Get system statistics
     *
     * @return array<string, int|string|float>
     */
    private function getSystemStats(): array
    {
        // Calculate storage usage in MB - check if directory exists first
        $storageUsage = 0;
        if (Storage::disk('public')->exists('tracks')) {
            $storageUsage = round(Storage::disk('public')->size('tracks') / (1024 * 1024), 2);
        }

        $trackCount = Track::count();
        $genreCount = Genre::count();
        $playlistCount = Playlist::count();

        // Calculate total duration in a more efficient way
        $totalSeconds = 0;

        // Only select the duration column to minimize data transfer
        $tracks = Track::select('duration')
            ->whereNotNull('duration')
            ->where('duration', '!=', '')
            ->get();

        foreach ($tracks as $track) {
            if (strpos($track->duration, ':') !== false) {
                $parts = explode(':', $track->duration);
                if (count($parts) === 2) {
                    $totalSeconds += (int) $parts[0] * 60 + (int) $parts[1];
                }
            }
        }

        // Format total seconds to MM:SS format
        $minutes = floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;
        $totalDuration = sprintf('%d:%02d', $minutes, $seconds);

        return [
            'tracksCount' => $trackCount,
            'genresCount' => $genreCount,
            'playlistsCount' => $playlistCount,
            'totalDuration' => $totalDuration,
            'storage' => $storageUsage,
        ];
    }

    public function render()
    {
        return response()->json([
            'tracksCount' => $this->stats['tracksCount'],
            'genresCount' => $this->stats['genresCount'],
            'playlistsCount' => $this->stats['playlistsCount'],
            'totalDuration' => $this->stats['totalDuration'],
            'storage' => $this->stats['storage'],
            'tracks' => $this->stats['tracksCount'],
            'genres' => $this->stats['genresCount'],
            'playlists' => $this->stats['playlistsCount'],
        ]);
    }
} 