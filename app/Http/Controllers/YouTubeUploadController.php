<?php

namespace App\Http\Controllers;

use App\Jobs\UploadTrackToYouTube;
use App\Models\Track;
use App\Services\YouTubeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        
        // Dispatch the upload job
        UploadTrackToYouTube::dispatch(
            $track,
            $validated['title'],
            $validated['description'] ?? '',
            (bool) ($validated['add_to_playlist'] ?? true),
            $validated['privacy_status']
        );
        
        return redirect()->route('tracks.index')
            ->with('success', 'Track upload to YouTube has been queued');
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
        
        // Dispatch the upload job
        UploadTrackToYouTube::dispatch(
            $track,
            $track->title,
            "Uploaded from SunoPanel\nOriginal track: {$track->title}",
            true,
            'unlisted'
        );
        
        return redirect()->route('tracks.show', $track->id)
            ->with('success', 'Track upload to YouTube has been queued');
    }
} 