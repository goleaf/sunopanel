<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessTrack;
use App\Models\Track;
use App\Models\Genre;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class HomeController extends Controller
{
    /**
     * Show the dashboard with tracks overview.
     */
    public function index(Request $request): View
    {
        // Get tracks for the dashboard (limited for performance)
        $query = Track::with('genres');
        
        // Apply global YouTube visibility filter
        $globalFilter = Setting::get('global_filter', 'all');
        match ($globalFilter) {
            'uploaded_only' => $query->uploadedToYoutube(),
            'not_uploaded_only' => $query->notUploadedToYoutube(),
            default => null,
        };
        
        // Apply search filter if provided
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('genres_string', 'like', "%{$searchTerm}%");
            });
        }
        
        // Filter by status if provided
        if ($request->filled('status') && in_array($request->input('status'), Track::$statuses)) {
            $query->withStatus($request->input('status'));
        }
        
        // Filter by genre if provided
        if ($request->filled('genre')) {
            $genre = $request->input('genre');
            $query->whereHas('genres', fn($q) => $q->where('genres.id', $genre));
        }
        
        // Get paginated tracks sorted by status priority
        $sortDirection = $request->input('direction', 'desc');
        $sortColumn = $request->input('sort', 'created_at');
        
        if ($sortColumn === 'created_at') {
            $tracks = $query->orderByRaw("CASE 
                    WHEN status = 'processing' THEN 1 
                    WHEN status = 'pending' THEN 2
                    WHEN status = 'failed' THEN 3
                    WHEN status = 'stopped' THEN 4
                    WHEN status = 'completed' THEN 5
                    ELSE 6 END")
                ->orderBy('created_at', $sortDirection)
                ->paginate(15)
                ->withQueryString();
        } else {
            $tracks = $query->orderBy($sortColumn, $sortDirection)
                ->paginate(15)
                ->withQueryString();
        }
        
        // Get genres with track counts
        $genres = Genre::withCount('tracks')->orderBy('name')->get();
        
        // Calculate statistics
        $statsQuery = Track::query();
        
        // Apply the same global filter to stats
        match ($globalFilter) {
            'uploaded_only' => $statsQuery->uploadedToYoutube(),
            'not_uploaded_only' => $statsQuery->notUploadedToYoutube(),
            default => null,
        };
        
        $stats = [
            'total' => $statsQuery->count(),
            'completed' => $statsQuery->where('status', 'completed')->count(),
            'processing' => $statsQuery->where('status', 'processing')->count(),
            'pending' => $statsQuery->where('status', 'pending')->count(),
            'failed' => $statsQuery->where('status', 'failed')->count(),
            'uploaded_to_youtube' => $statsQuery->whereNotNull('youtube_video_id')->count(),
        ];
        
        // Get settings
        $settings = [
            'global_filter' => Setting::get('global_filter', 'all'),
            'youtube_column_visible' => Setting::get('youtube_column_visible', true),
        ];
        
        return view('home.index', compact('tracks', 'genres', 'stats', 'settings'));
    }

    /**
     * Process the submitted tracks.
     */
    public function process(Request $request): RedirectResponse
    {
        $request->validate([
            'tracks_input' => 'required|string|min:10',
        ]);

        $tracksText = $request->input('tracks_input');
        $lines = explode("\n", $tracksText);
        $createdTracks = [];

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) {
                continue;
            }
            
            try {
                $track = $this->parseAndCreateTrack($line);
                if ($track) {
                    // Dispatch the processing job
                    ProcessTrack::dispatch($track);
                    $createdTracks[] = $track;
                    
                    Log::info("Track queued for processing: {$track->title}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to process track: {$e->getMessage()}", [
                    'line' => $line,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $count = count($createdTracks);
        
        return redirect()->route('genres.index')
            ->with('success', "{$count} tracks have been queued for processing");
    }

    /**
     * Process the submitted tracks immediately and check for failures.
     */
    public function processImmediate(Request $request): RedirectResponse
    {
        $request->validate([
            'tracks_input' => 'required|string|min:10',
        ]);

        $tracksText = $request->input('tracks_input');
        $lines = explode("\n", $tracksText);
        $createdTracks = [];
        $failedTracks = [];

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) {
                continue;
            }
            
            try {
                $track = $this->parseAndCreateTrack($line);
                if ($track) {
                    $createdTracks[] = $track;
                    
                    Log::info("Starting immediate processing: {$track->title}");
                    
                    // Process the track immediately instead of dispatching
                    try {
                        $job = new ProcessTrack($track);
                        $job->handle();
                        Log::info("Immediate processing completed: {$track->title}");
                    } catch (\Exception $e) {
                        Log::error("Failed to process track immediately: {$e->getMessage()}", [
                            'track_id' => $track->id,
                            'title' => $track->title,
                            'error' => $e->getMessage(),
                        ]);
                        
                        $failedTracks[] = [
                            'track' => $track,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to create track: {$e->getMessage()}", [
                    'line' => $line,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $successCount = count($createdTracks) - count($failedTracks);
        $failCount = count($failedTracks);
        
        if ($failCount > 0) {
            // Store failed tracks in session for display
            $request->session()->flash('failed_tracks', $failedTracks);
            return redirect()->route('genres.index')
                ->with('warning', "Processed {$successCount} tracks successfully, {$failCount} tracks failed. See details below.");
        }
        
        return redirect()->route('genres.index')
            ->with('success', "All {$successCount} tracks were processed successfully!");
    }

    /**
     * Parse a track line and create a Track model.
     */
    private function parseAndCreateTrack(string $line): ?Track
    {
        // Parse the line
        $parts = explode('|', $line);
        
        if (count($parts) < 3) {
            Log::warning("Invalid track format: {$line}");
            return null;
        }
        
        $fileName = trim($parts[0]);
        $mp3Url = trim($parts[1]);
        $imageUrl = trim($parts[2]);
        $genresString = isset($parts[3]) ? trim($parts[3]) : '';
        
        // Clean up title (remove .mp3 extension)
        $title = str_replace('.mp3', '', $fileName);
        
        // Create/update the track
        return Track::updateOrCreate(
            ['title' => $title],
            [
                'mp3_url' => $mp3Url,
                'image_url' => $imageUrl,
                'genres_string' => $genresString,
                'status' => 'pending',
                'progress' => 0,
            ]
        );
    }
}
