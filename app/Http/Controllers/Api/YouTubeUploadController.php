<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UploadTrackToYouTube;
use App\Models\Track;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final readonly class YouTubeUploadController extends Controller
{
    /**
     * Upload a single track to YouTube.
     *
     * @param int $trackId
     * @return JsonResponse
     */
    public function uploadTrack(int $trackId): JsonResponse
    {
        try {
            $track = Track::findOrFail($trackId);
            
            // Check if track is completed
            if (!$track->is_completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Track is not completed yet'
                ], 400);
            }
            
            // Check if track has an mp4 file
            if (!$track->mp4_file) {
                return response()->json([
                    'success' => false,
                    'message' => 'Track does not have an mp4 file'
                ], 400);
            }
            
            // Check if file exists
            $videoPath = storage_path('app/public/videos/' . $track->mp4_file);
            if (!file_exists($videoPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video file does not exist'
                ], 400);
            }
            
            // Check if already uploaded to YouTube
            if ($track->youtube_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Track is already uploaded to YouTube'
                ], 400);
            }
            
            // Dispatch the upload job
            UploadTrackToYouTube::dispatch($track);
            
            return response()->json([
                'success' => true,
                'message' => 'Track queued for upload to YouTube'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error uploading track to YouTube: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Upload all eligible tracks to YouTube.
     *
     * @return JsonResponse
     */
    public function uploadAllTracks(): JsonResponse
    {
        try {
            // Get all completed tracks that have mp4 file and not uploaded to YouTube yet
            $tracks = Track::where('is_completed', true)
                ->whereNotNull('mp4_file')
                ->whereNull('youtube_id')
                ->get();
            
            $uploadCount = 0;
            
            foreach ($tracks as $track) {
                // Check if file exists
                $videoPath = storage_path('app/public/videos/' . $track->mp4_file);
                if (file_exists($videoPath)) {
                    // Dispatch the upload job
                    UploadTrackToYouTube::dispatch($track);
                    $uploadCount++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => $uploadCount . ' tracks queued for upload to YouTube'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error uploading tracks to YouTube: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get the upload status of all tracks.
     *
     * @return JsonResponse
     */
    public function getUploadStatus(): JsonResponse
    {
        try {
            $totalTracks = Track::count();
            $completedTracks = Track::where('is_completed', true)->count();
            $uploadedToYouTube = Track::whereNotNull('youtube_id')->count();
            $pendingUploads = Track::where('is_completed', true)
                ->whereNotNull('mp4_file')
                ->whereNull('youtube_id')
                ->count();
                
            return response()->json([
                'success' => true,
                'data' => [
                    'total_tracks' => $totalTracks,
                    'completed_tracks' => $completedTracks,
                    'uploaded_to_youtube' => $uploadedToYouTube,
                    'pending_uploads' => $pendingUploads
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting YouTube upload status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
} 