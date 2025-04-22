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
        
        $tracks = \App\Models\Track::whereNotNull('youtube_video_id')
            ->orderBy('youtube_uploaded_at', 'desc')
            ->get();
        
        // Extract all YouTube video IDs from the tracks
        $videoIds = $tracks->pluck('youtube_video_id')->filter()->toArray();
        
        // Get YouTube statistics if we have video IDs and the service is available
        $videoStats = [];
        if (!empty($videoIds) && $this->youtubeService->isAuthenticated()) {
            $videoStats = $this->youtubeService->getVideoStatistics($videoIds);
        }
        
        return view('youtube.index', [
            'credential' => $credential,
            'isAuthenticated' => $isAuthenticated,
            'authUrl' => $authUrl,
            'tracks' => $tracks,
            'videoStats' => $videoStats
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
        $tracks = \App\Models\Track::whereNotNull('youtube_video_id')
            ->orderBy('youtube_uploaded_at', 'desc')
            ->paginate(15);
            
        // Count total tracks with YouTube videos
        $totalUploads = \App\Models\Track::whereNotNull('youtube_video_id')->count();
        
        // Count total tracks with MP4 files that could be uploaded
        $tracksReadyToUpload = \App\Models\Track::where('status', 'completed')
            ->whereNotNull('mp4_path')
            ->whereNull('youtube_video_id')
            ->count();
        
        // Get statistics for the videos if we have any tracks
        $videoStats = [];
        $totalViews = 0;
        
        if ($tracks->isNotEmpty() && $this->youtubeService->isAuthenticated()) {
            try {
                // Extract all video IDs
                $videoIds = $tracks->pluck('youtube_video_id')->filter()->toArray();
                
                // Get statistics for all videos in one API call
                $videoStats = $this->youtubeService->getVideoStatistics($videoIds);
                
                // Calculate total views
                foreach ($videoStats as $stat) {
                    $totalViews += (int)$stat['viewCount'];
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch YouTube statistics: ' . $e->getMessage());
            }
        }
            
        return view('youtube.uploads', compact(
            'tracks', 
            'totalUploads', 
            'tracksReadyToUpload',
            'videoStats',
            'totalViews'
        ));
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
            'privacy_status' => 'required|in:public,unlisted,private',
            'not_for_kids' => 'nullable|boolean',
            'is_short' => 'nullable|boolean',
            'is_regular_video' => 'nullable|boolean',
            'add_to_playlist' => 'nullable|boolean',
        ]);
        
        try {
            if (!$this->youtubeService->isAuthenticated()) {
                return back()->with('error', 'YouTube authentication required. Please authenticate first.');
            }
            
            $track = \App\Models\Track::findOrFail($validated['track_id']);
            
            // Check if the track has a video file
            if (empty($track->mp4_path)) {
                return back()->with('error', 'Track does not have a video file.');
            }
            
            // Use SimpleYouTubeUploader for direct upload
            $uploader = app(\App\Services\SimpleYouTubeUploader::class);
            
            // Set default values
            $notForKids = $validated['not_for_kids'] ?? true;
            $isShort = $validated['is_short'] ?? true;
            $isRegularVideo = $validated['is_regular_video'] ?? true;
            $addToPlaylist = $validated['add_to_playlist'] ?? true;
            
            $successMessages = [];
            $videoIds = [];
            
            // Upload as regular video if selected
            if ($isRegularVideo) {
                // Upload the track as regular video
                $videoId = $uploader->uploadTrack(
                    $track,
                    $validated['title'],
                    $validated['description'] ?? $track->title,
                    $validated['privacy_status'],
                    $addToPlaylist,
                    false, // Not a short
                    !$notForKids // Inverse of "not for kids" is "made for kids"
                );
                
                $videoIds[] = $videoId;
                $successMessages[] = "Track uploaded successfully to YouTube!";
                
                // Add to genre-based playlists if requested
                if ($addToPlaylist && !empty($track->genres_string)) {
                    $genres = array_map('trim', explode(',', $track->genres_string));
                    
                    foreach ($genres as $genre) {
                        if (!empty($genre)) {
                            $playlistId = $this->youtubeService->findOrCreatePlaylist(
                                $genre,
                                "AI Music - {$genre}",
                                $validated['privacy_status']
                            );
                            
                            if ($playlistId) {
                                $this->youtubeService->addVideoToPlaylist($videoId, $playlistId);
                                $successMessages[] = "Added to '{$genre}' playlist.";
                            }
                        }
                    }
                }
            }
            
            // Upload as Short if selected
            if ($isShort) {
                // Create a shorts-specific title (optional)
                $shortsTitle = $validated['title'] . " #Shorts";
                
                // Upload the track as a Short
                $shortsVideoId = $uploader->uploadTrack(
                    $track,
                    $shortsTitle,
                    $validated['description'] ?? $track->title,
                    $validated['privacy_status'],
                    false, // Don't add Shorts to playlists
                    true,  // Is a Short
                    !$notForKids // Inverse of "not for kids" is "made for kids"
                );
                
                $videoIds[] = $shortsVideoId;
                $successMessages[] = "Track uploaded successfully to YouTube Shorts!";
            }
            
            // Create success message
            $message = implode(' ', $successMessages);
            if (count($videoIds) > 0) {
                $message .= " Video ID(s): " . implode(', ', $videoIds);
            }
            
            return redirect()->route('youtube.uploads')
                ->with('success', $message);
                
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
            
            // Determine if it should be uploaded as a Short (50% chance for testing)
            $isShort = (bool)rand(0, 1);
            
            // Upload the track
            $videoId = $uploader->uploadTrack(
                $track,
                $title,
                'Test upload - AI generated music',
                'unlisted',
                false, // Don't add to playlists for test uploads
                $isShort
            );
            
            $videoType = $isShort ? 'YouTube Shorts' : 'YouTube';
            
            return redirect()->route('youtube.status')
                ->with('success', "Test upload successful to {$videoType}! Video ID: {$videoId}");
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
    
    /**
     * Synchronize YouTube uploads with the database
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function syncUploads()
    {
        if (!$this->youtubeService->isAuthenticated()) {
            return redirect()->route('youtube.status')
                ->with('error', 'YouTube authentication required. Please authenticate first.');
        }
        
        try {
            // Get all uploaded videos from YouTube
            $videos = $this->youtubeService->listUploadedVideos();
            
            if (empty($videos)) {
                return redirect()->route('youtube.uploads')
                    ->with('info', 'No videos found on your YouTube channel.');
            }
            
            $syncCount = 0;
            $newCount = 0;
            $allVideoIds = [];
            
            foreach ($videos as $video) {
                $videoId = $video['id'];
                $title = $video['title'];
                $publishedAt = $video['publishedAt'];
                $allVideoIds[] = $videoId;
                
                // Check if track exists with this video ID
                $track = \App\Models\Track::where('youtube_video_id', $videoId)->first();
                
                if ($track) {
                    // Update existing track's YouTube data
                    $track->youtube_uploaded_at = date('Y-m-d H:i:s', strtotime($publishedAt));
                    $track->save();
                    $syncCount++;
                } else {
                    // Try to match by title
                    $possibleMatch = \App\Models\Track::where('title', 'like', "%{$title}%")
                        ->whereNull('youtube_video_id')
                        ->first();
                    
                    if ($possibleMatch) {
                        $possibleMatch->youtube_video_id = $videoId;
                        $possibleMatch->youtube_uploaded_at = date('Y-m-d H:i:s', strtotime($publishedAt));
                        $possibleMatch->save();
                        $newCount++;
                    }
                }
            }
            
            // Get statistics for synchronized videos
            $totalViews = 0;
            $videoStats = $this->youtubeService->getVideoStatistics($allVideoIds);
            
            foreach ($videoStats as $stat) {
                $totalViews += (int)$stat['viewCount'];
            }
            
            $viewsFormatted = number_format($totalViews);
            
            return redirect()->route('youtube.uploads')
                ->with('success', "YouTube synchronization completed. Updated {$syncCount} existing tracks and matched {$newCount} new tracks. Total views: {$viewsFormatted}");
                
        } catch (\Exception $e) {
            Log::error('YouTube sync failed: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('youtube.uploads')
                ->with('error', 'Failed to sync with YouTube: ' . $e->getMessage());
        }
    }
    
    /**
     * Refresh video statistics from YouTube
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refreshVideoStats(Request $request)
    {
        try {
            // Get all tracks with YouTube video IDs
            $tracks = \App\Models\Track::whereNotNull('youtube_video_id')->get();
            
            if ($tracks->isEmpty()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'No YouTube videos found to refresh statistics.',
                        'videoStats' => []
                    ]);
                }
                
                return redirect()->route('youtube.uploads')
                    ->with('info', 'No YouTube videos found to refresh statistics.');
            }
            
            // Get video IDs
            $videoIds = $tracks->pluck('youtube_video_id')->filter()->toArray();
            
            // Get stats from YouTube
            $videoStats = $this->youtubeService->getVideoStatistics($videoIds);
            
            // Calculate total views
            $totalViews = 0;
            foreach ($videoStats as $stat) {
                $totalViews += (int)($stat['viewCount'] ?? 0);
            }
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Video statistics refreshed successfully.',
                    'videoStats' => $videoStats,
                    'totalViews' => $totalViews
                ]);
            }
            
            // Store stats in session for display
            session(['youtube_video_stats' => $videoStats]);
            session(['youtube_total_views' => $totalViews]);
            
            return redirect()->route('youtube.uploads')
                ->with('success', 'Video statistics refreshed successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to refresh YouTube video statistics: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to refresh video statistics: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('youtube.uploads')
                ->with('error', 'Failed to refresh video statistics: ' . $e->getMessage());
        }
    }
    
    /**
     * Toggle YouTube enabled status for a track
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleYoutubeEnabled(Request $request)
    {
        $validated = $request->validate([
            'track_id' => 'required|exists:tracks,id',
        ]);
        
        try {
            $track = \App\Models\Track::findOrFail($validated['track_id']);
            $success = $track->toggleYoutubeEnabled();
            
            return response()->json([
                'success' => $success,
                'enabled' => $track->youtube_enabled,
                'message' => 'YouTube upload status ' . ($track->youtube_enabled ? 'enabled' : 'disabled') . ' for this track.'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to toggle YouTube enabled status', [
                'track_id' => $validated['track_id'],
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle YouTube status: ' . $e->getMessage()
            ], 500);
        }
    }
}
