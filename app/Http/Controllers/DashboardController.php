<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use App\Services\Logging\LoggingServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    private readonly LoggingServiceInterface $loggingService;

    public function __construct(LoggingServiceInterface $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * Display the dashboard with system stats
     */
    public function index(): View
    {
        $this->loggingService->logInfoMessage('Loading dashboard index page');
        // Get basic system stats
        $stats = $this->getSystemStats();

        return view('dashboard', compact('stats'));
    }

    /**
     * Return system statistics JSON for API use
     */
    public function systemStats(): JsonResponse
    {
        $this->loggingService->logInfoMessage('API request for system stats');
        $stats = $this->getSystemStats();

        // Include both naming conventions for backwards compatibility
        return response()->json([
            'tracksCount' => $stats['tracksCount'],
            'genresCount' => $stats['genresCount'],
            'playlistsCount' => $stats['playlistsCount'],
            'totalDuration' => $stats['totalDuration'],
            'storage' => $stats['storage'],
            'tracks' => $stats['tracksCount'],
            'genres' => $stats['genresCount'],
            'playlists' => $stats['playlistsCount'],
        ]);
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

        $this->loggingService->logInfoMessage("System stats - Tracks: {$trackCount}, Genres: {$genreCount}, Playlists: {$playlistCount}, Total Duration: {$totalDuration}");

        return [
            'tracksCount' => $trackCount,
            'genresCount' => $genreCount,
            'playlistsCount' => $playlistCount,
            'totalDuration' => $totalDuration,
            'storage' => $storageUsage,
        ];
    }
}
