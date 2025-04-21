<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTrack;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the add tracks form.
     */
    public function index(): View
    {
        return view('home.index');
    }

    /**
     * Process the submitted tracks.
     */
    public function process(Request $request)
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
                // Parse the line
                $parts = explode('|', $line);
                
                if (count($parts) < 3) {
                    Log::warning("Invalid track format: {$line}");
                    continue;
                }
                
                $fileName = trim($parts[0]);
                $mp3Url = trim($parts[1]);
                $imageUrl = trim($parts[2]);
                $genresString = isset($parts[3]) ? trim($parts[3]) : '';
                
                // Clean up title (remove .mp3 extension)
                $title = str_replace('.mp3', '', $fileName);
                
                // Create/update the track
                $track = Track::updateOrCreate(
                    ['title' => $title],
                    [
                        'mp3_url' => $mp3Url,
                        'image_url' => $imageUrl,
                        'genres_string' => $genresString,
                        'status' => 'pending',
                        'progress' => 0,
                    ]
                );
                
                // Dispatch the processing job
                ProcessTrack::dispatch($track);
                
                $createdTracks[] = $track;
                
                Log::info("Track queued for processing: {$track->title}");
            } catch (\Exception $e) {
                Log::error("Failed to process track: {$e->getMessage()}", [
                    'line' => $line,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $count = count($createdTracks);
        
        return redirect()->route('tracks.index')
            ->with('success', "{$count} tracks have been queued for processing");
    }

    /**
     * Process the submitted tracks immediately and check for failures.
     */
    public function processImmediate(Request $request)
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
                // Parse the line
                $parts = explode('|', $line);
                
                if (count($parts) < 3) {
                    Log::warning("Invalid track format: {$line}");
                    continue;
                }
                
                $fileName = trim($parts[0]);
                $mp3Url = trim($parts[1]);
                $imageUrl = trim($parts[2]);
                $genresString = isset($parts[3]) ? trim($parts[3]) : '';
                
                // Clean up title (remove .mp3 extension)
                $title = str_replace('.mp3', '', $fileName);
                
                // Create/update the track
                $track = Track::updateOrCreate(
                    ['title' => $title],
                    [
                        'mp3_url' => $mp3Url,
                        'image_url' => $imageUrl,
                        'genres_string' => $genresString,
                        'status' => 'pending',
                        'progress' => 0,
                    ]
                );
                
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
            return redirect()->route('tracks.index')
                ->with('warning', "Processed {$successCount} tracks successfully, {$failCount} tracks failed. See details below.");
        }
        
        return redirect()->route('tracks.index')
            ->with('success', "All {$successCount} tracks were processed successfully!");
    }
}
