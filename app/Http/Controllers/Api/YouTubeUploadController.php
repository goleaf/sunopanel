<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Track;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

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
            if ($track->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Track is not completed yet'
                ], 400);
            }
            
            // Check if track has an mp4 file
            if (empty($track->mp4_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Track does not have an mp4 file'
                ], 400);
            }
            
            // Use SimpleYouTubeUploader for direct upload
            $uploader = app(\App\Services\SimpleYouTubeUploader::class);
            
            if (!$uploader->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'message' => 'YouTube service is not authenticated'
                ], 400);
            }
            
            // Upload directly
            $videoId = $uploader->uploadTrack(
                $track,
                $track->title,
                $track->title, // Simple description
                'public'  // Default to public
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Track uploaded to YouTube successfully',
                'video_id' => $videoId,
                'youtube_url' => "https://www.youtube.com/watch?v={$videoId}"
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error uploading track to YouTube: ' . $e->getMessage(), [
                'track_id' => $trackId,
                'trace' => $e->getTraceAsString()
            ]);
            
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
            // Get all eligible tracks
            $tracks = Track::where('status', 'completed')
                ->whereNotNull('mp4_path')
                ->whereNull('youtube_video_id')
                ->get();
            
            if ($tracks->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No eligible tracks found for upload'
                ]);
            }
                
            // Use SimpleYouTubeUploader for uploads
            $uploader = app(\App\Services\SimpleYouTubeUploader::class);
            
            if (!$uploader->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'message' => 'YouTube service is not authenticated'
                ], 400);
            }
            
            $uploadCount = 0;
            $failedTracks = [];
            
            foreach ($tracks as $track) {
                try {
                    // Upload directly
                    $videoId = $uploader->uploadTrack(
                        $track,
                        null, // Use default title
                        null, // Use default description
                        'public'
                    );
                    
                    if ($videoId) {
                        $uploadCount++;
                    }
                    
                    // Small delay to avoid rate limits
                    usleep(500000); // 0.5 second delay
                } catch (\Exception $e) {
                    Log::error('Failed to upload track: ' . $e->getMessage(), [
                        'track_id' => $track->id
                    ]);
                    $failedTracks[] = $track->id;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => $uploadCount . ' tracks uploaded to YouTube successfully',
                'total_tracks' => $tracks->count(),
                'succeeded' => $uploadCount,
                'failed' => count($failedTracks),
                'failed_tracks' => $failedTracks
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error uploading tracks to YouTube: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
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