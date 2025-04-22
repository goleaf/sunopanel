<?php

namespace App\Http\Controllers;

use App\Models\YouTubeCredential;
use App\Services\YouTubeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class YouTubeController extends Controller
{
    protected YouTubeService $youtubeService;
    
    public function __construct(YouTubeService $youtubeService)
    {
        $this->youtubeService = $youtubeService;
    }
    
    /**
     * Show the YouTube configuration page
     */
    public function index()
    {
        $credential = YouTubeCredential::getLatest();
        $isAuthenticated = $this->youtubeService->isAuthenticated();
        $authUrl = $isAuthenticated ? null : $this->youtubeService->getAuthUrl();
        
        return view('youtube.index', [
            'credential' => $credential,
            'isAuthenticated' => $isAuthenticated,
            'authUrl' => $authUrl,
        ]);
    }
    
    /**
     * Show the YouTube status page
     */
    public function status()
    {
        $credential = YouTubeCredential::getLatest();
        $isAuthenticated = $this->youtubeService->isAuthenticated();
        
        return view('youtube.status', [
            'credential' => $credential,
            'isAuthenticated' => $isAuthenticated,
            'useOAuth' => $credential ? $credential->use_oauth : false,
            'useSimple' => $credential ? !$credential->use_oauth : true,
        ]);
    }
    
    /**
     * Redirect to Google for authentication
     */
    public function redirectToProvider()
    {
        $authUrl = $this->youtubeService->getAuthUrl();
        return redirect($authUrl);
    }
    
    /**
     * Handle callback from Google OAuth
     */
    public function handleCallback(Request $request)
    {
        if ($request->has('error')) {
            Log::error('YouTube authentication error: ' . $request->input('error'));
            return redirect()->route('youtube.status')
                ->with('error', 'Authentication failed: ' . $request->input('error'));
        }
        
        if (!$request->has('code')) {
            return redirect()->route('youtube.status')
                ->with('error', 'No authorization code provided');
        }
        
        try {
            $this->youtubeService->fetchAccessTokenWithAuthCode($request->input('code'));
            
            // Update credential model to set OAuth as enabled
            $credential = YouTubeCredential::getLatest() ?? new YouTubeCredential();
            $credential->use_oauth = true;
            $credential->save();
            
            return redirect()->route('youtube.status')
                ->with('success', 'Successfully authenticated with YouTube!');
        } catch (\Exception $e) {
            Log::error('Error handling YouTube callback: ' . $e->getMessage());
            return redirect()->route('youtube.status')
                ->with('error', 'Authentication failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Save YouTube API credentials
     */
    public function saveCredentials(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'redirect_uri' => 'required|string',
        ]);
        
        $this->youtubeService->saveClientCredentials(
            $validated['client_id'],
            $validated['client_secret'],
            $validated['redirect_uri']
        );
        
        return redirect()->route('youtube.status')
            ->with('success', 'YouTube API credentials saved successfully!');
    }
    
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        $credential = YouTubeCredential::getLatest();
        
        return view('youtube.login', [
            'credential' => $credential,
        ]);
    }
    
    /**
     * Show the uploads page
     */
    public function uploads()
    {
        $uploads = \App\Models\Track::whereNotNull('youtube_video_id')
            ->orderBy('youtube_uploaded_at', 'desc')
            ->paginate(15);
            
        return view('youtube.uploads', compact('uploads'));
    }
    
    /**
     * Show upload form
     */
    public function showUploadForm()
    {
        // Get completed tracks that have MP4 files
        $tracks = \App\Models\Track::where('status', 'completed')
            ->whereNotNull('mp4_file')
            ->orderBy('created_at', 'desc')
            ->get();
            
        $isAuthenticated = $this->youtubeService->isAuthenticated();
            
        return view('youtube.upload', compact('tracks', 'isAuthenticated'));
    }
    
    /**
     * Upload track to YouTube
     */
    public function uploadTrack(Request $request)
    {
        $validated = $request->validate([
            'track_id' => 'required|exists:tracks,id',
            'title' => 'required|string|max:100',
            'description' => 'nullable|string',
            'privacy' => 'required|in:public,unlisted,private',
            'playlist' => 'nullable|string',
        ]);
        
        try {
            if (!$this->youtubeService->isAuthenticated()) {
                return back()->with('error', 'YouTube authentication required. Please authenticate first.');
            }
            
            $track = \App\Models\Track::findOrFail($validated['track_id']);
            
            // Use SimpleYouTubeUploader for direct upload
            $uploader = app(\App\Services\SimpleYouTubeUploader::class);
            
            // Add to playlist if specified
            $addToPlaylist = !empty($validated['playlist']);
            
            // Upload the track
            $videoId = $uploader->uploadTrack(
                $track,
                $validated['title'],
                $validated['description'] ?? $track->title,
                $validated['privacy'],
                $addToPlaylist
            );
            
            // Add to custom playlist if specified
            if ($addToPlaylist) {
                $playlistId = $this->youtubeService->findOrCreatePlaylist(
                    $validated['playlist'],
                    "SunoPanel playlist - {$validated['playlist']}",
                    'public'
                );
                
                if ($playlistId) {
                    $this->youtubeService->addVideoToPlaylist($videoId, $playlistId);
                    $track->youtube_playlist_id = $playlistId;
                    $track->save();
                }
            }
            
            return redirect()->route('youtube.uploads')
                ->with('success', 'Track uploaded successfully to YouTube!');
        } catch (\Exception $e) {
            Log::error('YouTube upload failed', [
                'track_id' => $validated['track_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Failed to upload track to YouTube: ' . $e->getMessage());
        }
    }
    
    /**
     * Test the YouTube integration
     */
    public function testUpload(Request $request)
    {
        if (!$this->youtubeService->isAuthenticated()) {
            return redirect()->route('youtube.status')
                ->with('error', 'You must authenticate with YouTube first');
        }
        
        // Get a completed track for testing
        $track = \App\Models\Track::where('status', 'completed')
            ->whereNotNull('mp4_path')
            ->first();
            
        if (!$track) {
            return redirect()->route('youtube.status')
                ->with('error', 'No completed tracks found for testing');
        }
        
        try {
            // Use SimpleYouTubeUploader for test upload
            $uploader = app(\App\Services\SimpleYouTubeUploader::class);
            
            // Create a test title with timestamp
            $title = '[TEST] ' . $track->title . ' - ' . now()->format('Y-m-d H:i:s');
            
            // Upload the track
            $videoId = $uploader->uploadTrack(
                $track,
                $title,
                'This is a test upload from SunoPanel',
                'unlisted',
                false // Don't add to playlists for test uploads
            );
            
            return redirect()->route('youtube.status')
                ->with('success', 'Test upload successful! Video ID: ' . $videoId);
        } catch (\Exception $e) {
            Log::error('YouTube test upload failed', [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('youtube.status')
                ->with('error', 'Test upload failed: ' . $e->getMessage());
        }
    }
}
