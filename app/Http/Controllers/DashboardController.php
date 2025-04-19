<?php

namespace App\Http\Controllers;

use App\Models\Track;
use App\Models\Genre;
use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with system stats
     */
    public function index()
    {
        Log::info('Loading dashboard index page');
        // Get basic system stats
        $stats = $this->getSystemStats();
        
        return view('dashboard', compact('stats'));
    }
    
    /**
     * Return system statistics JSON for API use
     */
    public function systemStats()
    {
        Log::info('API request for system stats');
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
            'playlists' => $stats['playlistsCount']
        ]);
    }
    
    /**
     * Get system statistics
     * 
     * @return array
     */
    private function getSystemStats()
    {
        // Calculate storage usage in MB - check if directory exists first
        $storageUsage = 0;
        if (Storage::disk('public')->exists('tracks')) {
            $storageUsage = round(Storage::disk('public')->size('tracks') / (1024 * 1024), 2);
        }
        
        $trackCount = Track::count();
        $genreCount = Genre::count();
        $playlistCount = Playlist::count();
        
        Log::info("System stats - Tracks: {$trackCount}, Genres: {$genreCount}, Playlists: {$playlistCount}");
        
        return [
            'tracksCount' => $trackCount,
            'genresCount' => $genreCount,
            'playlistsCount' => $playlistCount,
            'totalDuration' => '0:00', // Add total duration calculation if needed
            'storage' => $storageUsage
        ];
    }
} 