<?php

namespace App\Http\Controllers;

use App\Jobs\UploadTrackToYouTube;
use App\Models\Track;
use App\Services\SimpleYouTubeUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoUploadController extends Controller
{
    /**
     * Show the video upload form
     */
    public function showUploadForm()
    {
        // Check if YouTube credentials are set
        $hasCredentials = !empty(config('youtube.email')) && !empty(config('youtube.password'));
        
        return view('videos.upload', [
            'hasCredentials' => $hasCredentials,
        ]);
    }
    
    /**
     * Upload a video directly to YouTube
     */
    public function uploadVideo(Request $request)
    {
        $validated = $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi,wmv|max:500000', // 500MB max
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:5000',
            'privacy_status' => 'required|in:public,unlisted,private',
            'tags' => 'nullable|string|max:500',
        ]);
        
        // Check if YouTube credentials are set
        if (empty(config('youtube.email')) || empty(config('youtube.password'))) {
            return redirect()->back()->with('error', 'YouTube credentials are not set. Please set them in the YouTube Settings page.');
        }
        
        try {
            // Store the uploaded video file temporarily
            $videoFile = $request->file('video');
            $videoFileName = Str::slug(pathinfo($videoFile->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . time() . '.' . $videoFile->getClientOriginalExtension();
            $videoPath = $videoFile->storeAs('public/temp', $videoFileName);
            $fullVideoPath = storage_path('app/' . $videoPath);
            
            // Prepare tags
            $tags = [];
            if (!empty($validated['tags'])) {
                $tags = explode(',', $validated['tags']);
                $tags = array_map('trim', $tags);
            }
            
            // Upload to YouTube directly
            $uploader = new SimpleYouTubeUploader();
            
            // Create a temporary Track object
            $track = new Track();
            $track->title = $validated['title'];
            $track->description = $validated['description'] ?? '';
            $track->mp4_path = 'temp/' . $videoFileName;
            
            // Save track to get an ID
            $track->save();
            
            $videoId = $uploader->uploadTrack(
                $track,
                $validated['title'],
                $validated['description'] ?? '',
                $validated['privacy_status'],
                true, // add to playlist
                false, // not a short
                false  // not made for kids
            );
            
            if (!$videoId) {
                throw new \Exception('Failed to upload video to YouTube');
            }
            
            // Update the track record
            $track->update([
                'status' => 'completed',
                'youtube_video_id' => $videoId,
                'youtube_uploaded_at' => now(),
                'genres_string' => implode(', ', $tags),
                'mp4_file' => $videoFileName,
            ]);
            
            // Clean up the temporary file
            Storage::delete($videoPath);
            
            return redirect()->route('videos.success')->with([
                'success' => 'Video uploaded successfully to YouTube!',
                'videoId' => $videoId,
                'videoTitle' => $validated['title'],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Video upload error: ' . $e->getMessage());
            
            // Clean up any temporary files
            if (isset($videoPath) && Storage::exists($videoPath)) {
                Storage::delete($videoPath);
            }
            
            return redirect()->back()->with('error', 'Failed to upload video: ' . $e->getMessage());
        }
    }
    
    /**
     * Show upload success page
     */
    public function showSuccessPage()
    {
        if (!session('videoId')) {
            return redirect()->route('videos.upload');
        }
        
        return view('videos.success', [
            'videoId' => session('videoId'),
            'videoTitle' => session('videoTitle'),
        ]);
    }
} 