<?php

namespace App\Http\Controllers;

use App\Models\Track;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\YoutubeThumbnailGenerator;
use App\Services\TrackProcessor;
use Illuminate\Support\Facades\Artisan;
use App\Services\YouTubeService;

class TrackController extends Controller
{
    /**
     * Display a listing of the tracks.
     */
    public function index(Request $request): View
    {
        $query = Track::with('genres');
        
        // Apply search filter
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('genres_string', 'like', "%{$searchTerm}%");
            });
        }
        
        // Filter by status
        $validStatuses = ['pending', 'processing', 'completed', 'failed', 'stopped'];
        if ($request->filled('status') && in_array($request->input('status'), $validStatuses)) {
            $status = $request->input('status');
            $query->where('status', $status);
        }
        
        // Filter by genre
        if ($request->filled('genre')) {
            $genre = $request->input('genre');
            $query->whereHas('genres', function($q) use ($genre) {
                $q->where('slug', $genre);
            });
        }
        
        // Sort by status (processing first) then by created_at
        $tracks = $query->orderByRaw("CASE 
                WHEN status = 'processing' THEN 1 
                WHEN status = 'pending' THEN 2
                WHEN status = 'failed' THEN 3
                WHEN status = 'stopped' THEN 4
                WHEN status = 'completed' THEN 5
                ELSE 6 END")
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString(); // Keep the query string for pagination
        
        // Get track counts for stats display
        $totalTracks = Track::count();
        $processingTracks = Track::where('status', 'processing')->count();
        $pendingTracks = Track::where('status', 'pending')->count();
        $completedTracks = Track::where('status', 'completed')->count();
        $failedTracks = Track::where('status', 'failed')->count();
        $stoppedTracks = Track::where('status', 'stopped')->count();
        
        // Add processing + pending count for the UI
        $activeTracksCount = $processingTracks + $pendingTracks;
                        
        return view('tracks.index', compact(
            'tracks', 
            'totalTracks', 
            'processingTracks', 
            'pendingTracks',
            'activeTracksCount',
            'completedTracks', 
            'failedTracks',
            'stoppedTracks'
        ));
    }

    /**
     * Display the specified track.
     */
    public function show(Track $track): View
    {
        $track->load('genres');
        return view('tracks.show', compact('track'));
    }

    /**
     * Remove the specified track from storage.
     */
    public function destroy(Track $track)
    {
        // Delete associated files
        if ($track->mp3_path) {
            \Storage::disk('public')->delete($track->mp3_path);
        }
        
        if ($track->image_path) {
            \Storage::disk('public')->delete($track->image_path);
        }
        
        if ($track->mp4_path) {
            \Storage::disk('public')->delete($track->mp4_path);
        }
        
        // Delete record
        $track->genres()->detach();
        $track->delete();
        
        return redirect()->route('tracks.index')
            ->with('success', "Track '{$track->title}' has been deleted.");
    }

    /**
     * Get the current status of a track (for AJAX requests).
     */
    public function status(Track $track): JsonResponse
    {
        $track->load('genres');
        return response()->json([
            'id' => $track->id,
            'status' => $track->status,
            'progress' => $track->progress,
            'error_message' => $track->error_message,
            'genres' => $track->genres->pluck('name'),
        ]);
    }
    
    /**
     * Retry processing a failed track.
     */
    public function retry(Request $request, Track $track)
    {
        // Reset track status
        $track->update([
            'status' => 'pending',
            'progress' => 0,
            'error_message' => null,
        ]);
        
        // Dispatch the job
        \App\Jobs\ProcessTrack::dispatch($track);
        
        // Check if we should redirect back
        if ($request->has('redirect_back')) {
            return back()->with('success', "Track '{$track->title}' has been requeued for processing.");
        }
        
        // Default behavior is to redirect to the tracks index
        return redirect()->route('tracks.index')
            ->with('success', "Track '{$track->title}' has been requeued for processing.");
    }

    /**
     * Retry processing all failed tracks.
     */
    public function retryAll(Request $request)
    {
        // Get all failed tracks
        $failedTracks = Track::where('status', 'failed')->get();
        $count = $failedTracks->count();
        
        if ($count === 0) {
            return redirect()->route('tracks.index')
                ->with('info', 'No failed tracks to retry.');
        }
        
        // Reset status and dispatch jobs for all failed tracks
        foreach ($failedTracks as $track) {
            $track->update([
                'status' => 'pending',
                'progress' => 0,
                'error_message' => null,
            ]);
            
            \App\Jobs\ProcessTrack::dispatch($track);
        }
        
        return redirect()->route('tracks.index')
            ->with('success', "{$count} failed tracks have been requeued for processing.");
    }

    /**
     * Upload a track to YouTube.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Track $track
     * @return \Illuminate\Http\Response
     */
    public function uploadToYoutube(Request $request, Track $track)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'nullable|string',
            'privacy_status' => 'required|in:public,unlisted,private',
        ]);

        try {
            // Check if track is completed and has an MP4 file
            if ($track->status !== 'completed' || empty($track->mp4_path)) {
                return back()->with('error', 'Track must be completed and have a video file to upload to YouTube.');
            }
            
            // Check YouTube authentication
            $youtubeService = app(YouTubeService::class);
            if (!$youtubeService->isAuthenticated()) {
                return redirect()->route('youtube.auth.redirect')
                    ->with('warning', 'YouTube authentication required. Please authenticate first.');
            }
            
            // Check if the file exists before attempting upload
            $videoPath = storage_path('app/public/' . $track->mp4_path);
            if (!file_exists($videoPath)) {
                \Log::error('MP4 file not found for track', [
                    'track_id' => $track->id,
                    'mp4_path' => $track->mp4_path,
                    'expected_path' => $videoPath
                ]);
                
                return back()->with('error', 'MP4 file not found. Please contact administrator.');
            }
            
            // Run the Artisan command to upload to YouTube
            $exitCode = Artisan::call('youtube:upload', [
                '--track_id' => $track->id,
                '--title' => $request->title,
                '--description' => $request->description ?? '',
                '--privacy' => $request->privacy_status, // Changed to match form field name
            ]);
            
            if ($exitCode !== 0) {
                $output = Artisan::output();
                \Log::error('YouTube upload failed via command', [
                    'track_id' => $track->id,
                    'command_output' => $output,
                ]);
                return back()->with('error', 'Failed to upload track to YouTube. Check logs for details.');
            }
            
            return back()->with('success', 'Track uploaded successfully to YouTube!');
        } catch (\Exception $e) {
            \Log::error('YouTube upload failed', [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Failed to upload track to YouTube: ' . $e->getMessage());
        }
    }

    /**
     * Upload all completed tracks to YouTube.
     */
    public function uploadAllToYoutube(Request $request)
    {
        // Get all completed tracks that have an MP4 file and haven't been uploaded to YouTube yet
        $tracks = Track::where('status', 'completed')
            ->whereNotNull('mp4_file')
            ->where(function($query) {
                $query->whereNull('youtube_video_id')
                    ->orWhere('youtube_video_id', '');
            })
            ->get();
        
        $count = $tracks->count();
        
        if ($count === 0) {
            return redirect()->route('tracks.index')
                ->with('info', 'No eligible tracks found for YouTube upload.');
        }
        
        // Use the privacy status from the form or default to 'unlisted'
        $privacyStatus = $request->input('privacy_status', 'unlisted');
        
        // Run command to upload all eligible tracks
        $exitCode = Artisan::call('youtube:upload-all', [
            '--privacy' => $privacyStatus,
        ]);
        
        if ($exitCode !== 0) {
            $output = Artisan::output();
            \Log::error('YouTube bulk upload failed via command', [
                'command_output' => $output,
            ]);
            return redirect()->route('tracks.index')
                ->with('error', 'Failed to upload tracks to YouTube. Check logs for details.');
        }
        
        return redirect()->route('tracks.index')
            ->with('success', "Tracks have been queued for upload to YouTube.");
    }
}
