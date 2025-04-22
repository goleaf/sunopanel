<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Services\SimpleYouTubeUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class YouTubeController extends Controller
{
    /**
     * Upload a single video to YouTube
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function uploadVideo(Request $request, int $id): JsonResponse
    {
        try {
            // Find the video
            $video = Video::find($id);
            
            if (!$video) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video not found'
                ], 404);
            }
            
            // Check if already uploaded
            if ($video->youtube_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video already uploaded to YouTube',
                    'youtube_id' => $video->youtube_id
                ], 400);
            }
            
            // Check if file exists
            $videoPath = storage_path('app/public/videos/' . $video->filename);
            if (!file_exists($videoPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video file not found on disk'
                ], 404);
            }
            
            // Process tags
            $tags = $video->tags ? explode(',', $video->tags) : [];
            
            // Get privacy status from request or default to 'unlisted'
            $privacyStatus = $request->input('privacy_status', 'unlisted');
            
            // Get the YouTubeUploader service
            $uploader = app(SimpleYouTubeUploader::class);
            
            // Upload to YouTube
            $youtubeId = $uploader->upload(
                $videoPath,
                $video->title,
                $video->description ?? '',
                $tags,
                $privacyStatus,
                'Music' // Default category
            );
            
            if (!$youtubeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload video to YouTube'
                ], 500);
            }
            
            // Update the video record with the YouTube ID
            $video->youtube_id = $youtubeId;
            $video->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Video successfully uploaded to YouTube',
                'youtube_id' => $youtubeId
            ]);
            
        } catch (\Exception $e) {
            Log::error('YouTube upload error: ' . $e->getMessage(), [
                'video_id' => $id,
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error uploading video: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Trigger upload of all pending videos to YouTube
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadAll(Request $request): JsonResponse
    {
        try {
            // Get optional limit parameter
            $limit = $request->input('limit', null);
            
            // Get optional privacy status
            $privacyStatus = $request->input('privacy_status', 'unlisted');
            
            // Build command arguments
            $arguments = [];
            if ($limit) {
                $arguments['--limit'] = $limit;
            }
            if ($privacyStatus) {
                $arguments['--privacy'] = $privacyStatus;
            }
            
            // Run the command to upload all videos
            Artisan::queue('youtube:upload-all', $arguments);
            
            return response()->json([
                'success' => true,
                'message' => 'YouTube upload process initiated in the background'
            ]);
            
        } catch (\Exception $e) {
            Log::error('YouTube bulk upload error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error initiating bulk upload: ' . $e->getMessage()
            ], 500);
        }
    }
} 