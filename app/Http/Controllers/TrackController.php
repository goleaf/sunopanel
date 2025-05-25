<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Track;
use App\Models\Genre;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Services\YoutubeThumbnailGenerator;
use App\Services\TrackProcessor;
use App\Services\YouTubeService;
use App\Services\SimpleYouTubeUploader;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

final class TrackController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly YouTubeService $youtubeService,
        private readonly SimpleYouTubeUploader $youtubeUploader
    ) {
        // No middleware in constructor for Laravel 12
    }

    /**
     * Display a listing of the tracks.
     */
    public function index(Request $request): View
    {
        $query = Track::with('genres');
        
        // Apply global YouTube visibility filter
        $youtubeVisibilityFilter = Setting::get('youtube_visibility_filter', 'all');
        match ($youtubeVisibilityFilter) {
            'uploaded' => $query->uploadedToYoutube(),
            'not_uploaded' => $query->notUploadedToYoutube(),
            default => null,
        };
        
        // Apply search filter
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('genres_string', 'like', "%{$searchTerm}%");
            });
        }
        
        // Filter by status
        if ($request->filled('status') && in_array($request->input('status'), Track::$statuses)) {
            $query->withStatus($request->input('status'));
        }
        
        // Filter by genre
        if ($request->filled('genre')) {
            $genre = $request->input('genre');
            $query->whereHas('genres', fn($q) => $q->where('id', $genre));
        }
        
        // Sort by status priority then by created_at
        $tracks = $query->orderByRaw("CASE 
                WHEN status = 'processing' THEN 1 
                WHEN status = 'pending' THEN 2
                WHEN status = 'failed' THEN 3
                WHEN status = 'stopped' THEN 4
                WHEN status = 'completed' THEN 5
                ELSE 6 END")
            ->orderBy('created_at', 'desc')
            ->paginate(100)
            ->withQueryString();
        
        // Get track counts for stats display
        $statsQuery = Track::query();
        
        // Apply the same YouTube visibility filter to stats
        match ($youtubeVisibilityFilter) {
            'uploaded' => $statsQuery->uploadedToYoutube(),
            'not_uploaded' => $statsQuery->notUploadedToYoutube(),
            default => null,
        };
        
        $stats = $statsQuery->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "processing" THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN status = "stopped" THEN 1 ELSE 0 END) as stopped
        ')->first();
        
        $totalTracks = $stats->total;
        $processingTracks = $stats->processing;
        $pendingTracks = $stats->pending;
        $completedTracks = $stats->completed;
        $failedTracks = $stats->failed;
        $stoppedTracks = $stats->stopped;
        
        // Add processing + pending count for the UI
        $activeTracksCount = $processingTracks + $pendingTracks;
        
        // Get global settings for the view
        $showYoutubeColumn = Setting::get('show_youtube_column', true);
                        
        return view('tracks.index', compact(
            'tracks', 
            'totalTracks', 
            'processingTracks', 
            'pendingTracks',
            'activeTracksCount',
            'completedTracks', 
            'failedTracks',
            'stoppedTracks',
            'showYoutubeColumn',
            'youtubeVisibilityFilter'
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
    public function destroy(Track $track): RedirectResponse
    {
        try {
            // Delete associated files
            $this->deleteTrackFiles($track);
            
            // Delete record
            $track->genres()->detach();
            $track->delete();
            
            return redirect()->route('tracks.index')
                ->with('success', "Track '{$track->title}' has been deleted.");
        } catch (\Exception $e) {
            Log::error('Failed to delete track', [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->route('tracks.index')
                ->with('error', 'Failed to delete track. Please try again.');
        }
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
    public function retry(Request $request, Track $track): RedirectResponse|JsonResponse
    {
        try {
            // Reset track status
            $track->update([
                'status' => 'pending',
                'progress' => 0,
                'error_message' => null,
            ]);
            
            // Dispatch the job
            \App\Jobs\ProcessTrack::dispatch($track);
            
            $message = "Track '{$track->title}' has been requeued for processing.";
            
            // Check if we should redirect back
            if ($request->has('redirect_back')) {
                return back()->with('success', $message);
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'track' => [
                    'id' => $track->id,
                    'status' => $track->status,
                    'progress' => $track->progress,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retry track processing', [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
            ]);
            
            if ($request->has('redirect_back')) {
                return back()->with('error', 'Failed to retry track processing.');
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry track processing.',
            ], 500);
        }
    }
    
    /**
     * Retry all failed tracks.
     */
    public function retryAll(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $failedTracks = Track::where('status', 'failed')->get();
            
            if ($failedTracks->isEmpty()) {
                $message = 'No failed tracks to retry.';
                
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message]);
                }
                
                return back()->with('info', $message);
            }
            
            foreach ($failedTracks as $track) {
                $track->update([
                    'status' => 'pending',
                    'progress' => 0,
                    'error_message' => null,
                ]);
                
                \App\Jobs\ProcessTrack::dispatch($track);
            }
            
            $message = "Retrying {$failedTracks->count()} failed tracks.";
            
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to retry all tracks', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to retry tracks.'], 500);
            }
            
            return back()->with('error', 'Failed to retry tracks.');
        }
    }

    /**
     * Upload a track to YouTube.
     */
    public function uploadToYoutube(Request $request, Track $track): RedirectResponse
    {
        try {
            if (!$this->youtubeUploader->isAuthenticated()) {
                return redirect()->route('youtube.auth.redirect')
                    ->with('warning', 'YouTube authentication required. Please authenticate first.');
            }

            if ($track->status !== 'completed') {
                return back()->with('error', 'Track must be completed before uploading to YouTube.');
            }

            if ($track->youtube_video_id) {
                return back()->with('info', 'Track is already uploaded to YouTube.');
            }

            // Upload to YouTube
            $videoId = $this->youtubeUploader->uploadTrack($track);
            
            if ($videoId) {
                $track->update([
                    'youtube_video_id' => $videoId,
                    'youtube_uploaded_at' => now(),
                ]);
                
                return back()->with('success', "Track '{$track->title}' uploaded to YouTube successfully!");
            }
            
            return back()->with('error', 'Failed to upload track to YouTube.');
        } catch (\Exception $e) {
            Log::error('Failed to upload track to YouTube', [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Failed to upload track to YouTube: ' . $e->getMessage());
        }
    }

    /**
     * Toggle YouTube status for a track.
     */
    public function toggleYoutubeStatus(Request $request, Track $track): JsonResponse
    {
        try {
            $track->toggleYoutubeEnabled();
            
            return response()->json([
                'success' => true,
                'youtube_enabled' => $track->youtube_enabled,
                'message' => $track->youtube_enabled 
                    ? 'Track enabled for YouTube upload' 
                    : 'Track disabled for YouTube upload'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to toggle YouTube status', [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update YouTube status',
            ], 500);
        }
    }

    /**
     * Upload a random track to YouTube.
     */
    public function randomYoutubeUpload(): RedirectResponse
    {
        try {
            if (!$this->youtubeUploader->isAuthenticated()) {
                return redirect()->route('youtube.auth.redirect')
                    ->with('warning', 'YouTube authentication required. Please authenticate first.');
            }

            // Find a random completed track that hasn't been uploaded to YouTube
            $track = Track::completed()
                ->notUploadedToYoutube()
                ->where('youtube_enabled', true)
                ->inRandomOrder()
                ->first();

            if (!$track) {
                return back()->with('info', 'No eligible tracks found for YouTube upload.');
            }

            // Upload to YouTube
            $videoId = $this->youtubeUploader->uploadTrack($track);
            
            if ($videoId) {
                $track->update([
                    'youtube_video_id' => $videoId,
                    'youtube_uploaded_at' => now(),
                ]);
                
                return back()->with('success', "Random track '{$track->title}' uploaded to YouTube successfully!");
            }
            
            return back()->with('error', 'Failed to upload random track to YouTube.');
        } catch (\Exception $e) {
            Log::error('Failed to upload random track to YouTube', [
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Failed to upload random track to YouTube: ' . $e->getMessage());
        }
    }

    /**
     * Delete track files from storage.
     */
    private function deleteTrackFiles(Track $track): void
    {
        $files = array_filter([
            $track->mp3_path,
            $track->image_path,
            $track->mp4_path,
        ]);

        foreach ($files as $file) {
            if (Storage::disk('public')->exists($file)) {
                Storage::disk('public')->delete($file);
            }
        }
    }
}
