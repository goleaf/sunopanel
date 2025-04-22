<?php

namespace App\Http\Controllers;

use App\Models\Track;
use App\Services\YouTubeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class YouTubeUploadController extends Controller
{
    protected YouTubeService $youtubeService;
    
    public function __construct(YouTubeService $youtubeService)
    {
        $this->youtubeService = $youtubeService;
    }
    
    /**
     * Show the upload form
     */
    public function showUploadForm()
    {
        $isAuthenticated = $this->youtubeService->isAuthenticated();
        
        if (!$isAuthenticated) {
            return redirect()->route('youtube.auth.redirect')
                ->with('warning', 'You must authenticate with YouTube first');
        }
        
        // Get completed tracks that can be uploaded
        $tracks = Track::where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('youtube.upload', [
            'tracks' => $tracks,
            'isAuthenticated' => $isAuthenticated,
        ]);
    }
    
    /**
     * Upload a track to YouTube
     */
    public function uploadTrack(Request $request)
    {
        $validated = $request->validate([
            'track_id' => 'required|exists:tracks,id',
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:5000',
            'privacy_status' => 'required|in:public,unlisted,private',
            'add_to_playlist' => 'boolean',
        ]);
        
        $track = Track::findOrFail($validated['track_id']);
        
        if (!$this->youtubeService->isAuthenticated()) {
            return redirect()->route('youtube.auth.redirect')
                ->with('warning', 'You must authenticate with YouTube first');
        }
        
        // Use the Artisan command to upload the track
        $exitCode = Artisan::call('youtube:upload', [
            'track_id' => $track->id,
            '--title' => $validated['title'],
            '--description' => $validated['description'] ?? '',
            '--privacy' => $validated['privacy_status']
        ]);
        
        if ($exitCode !== 0) {
            $output = Artisan::output();
            Log::error('YouTube upload failed via command', [
                'track_id' => $track->id,
                'command_output' => $output,
            ]);
            return redirect()->route('tracks.index')
                ->with('error', 'Failed to upload track to YouTube. Check logs for details.');
        }
        
        return redirect()->route('tracks.index')
            ->with('success', 'Track has been successfully uploaded to YouTube');
    }
    
    /**
     * View the list of uploaded tracks
     */
    public function viewUploads()
    {
        $tracks = Track::whereNotNull('youtube_video_id')
            ->orderBy('youtube_uploaded_at', 'desc')
            ->paginate(20);
        
        return view('youtube.uploads', [
            'tracks' => $tracks,
        ]);
    }
    
    /**
     * Upload a specific track to YouTube directly (admin only)
     */
    public function uploadTrackDirect($id)
    {
        $track = Track::findOrFail($id);
        
        if (!$this->youtubeService->isAuthenticated()) {
            return redirect()->route('youtube.auth.redirect')
                ->with('warning', 'You must authenticate with YouTube first');
        }
        
        // Use the Artisan command to upload the track
        $exitCode = Artisan::call('youtube:upload', [
            'track_id' => $track->id,
            '--title' => $track->title,
            '--description' => "Uploaded from SunoPanel\nOriginal track: {$track->title}",
            '--privacy' => 'unlisted'
        ]);
        
        if ($exitCode !== 0) {
            $output = Artisan::output();
            Log::error('YouTube direct upload failed via command', [
                'track_id' => $track->id,
                'command_output' => $output,
            ]);
            return redirect()->route('tracks.show', $track->id)
                ->with('error', 'Failed to upload track to YouTube. Check logs for details.');
        }
        
        return redirect()->route('tracks.show', $track->id)
            ->with('success', 'Track has been successfully uploaded to YouTube');
    }
} 