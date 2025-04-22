<?php

namespace App\Services;

use App\Models\Track;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * A simplified wrapper around the YouTubeService for easier use in commands and controllers
 */
class SimpleYouTubeUploader
{
    /**
     * @var YouTubeService
     */
    protected $youtubeService;
    
    /**
     * Create a new SimpleYouTubeUploader instance
     */
    public function __construct()
    {
        $this->youtubeService = app(YouTubeService::class);
    }
    
    /**
     * Check if the YouTube service is authenticated
     * 
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->youtubeService->isAuthenticated();
    }
    
    /**
     * Upload a track to YouTube
     * 
     * @param Track $track Track to upload
     * @param string|null $title Custom title (defaults to track title)
     * @param string|null $description Custom description
     * @param string $privacy Video privacy setting (public, unlisted, private)
     * @param bool $addToPlaylist Whether to add to playlist
     * @param bool $isShort Whether to upload as a YouTube Short
     * @param bool $madeForKids Whether the content is made for kids
     * @return string|null The YouTube video ID if successful
     * @throws Exception If upload fails
     */
    public function uploadTrack(
        Track $track,
        ?string $title = null,
        ?string $description = null,
        string $privacy = 'public',
        bool $addToPlaylist = true,
        bool $isShort = false,
        bool $madeForKids = false
    ): ?string {
        // Check if the YouTube service is authenticated
        if (!$this->isAuthenticated()) {
            throw new Exception('YouTube service is not authenticated');
        }
        
        // Check if track has an MP4 file
        if (empty($track->mp4_path)) {
            throw new Exception('Track does not have an MP4 file');
        }
        
        // Get the path to the MP4 file
        $videoPath = storage_path('app/public/' . $track->mp4_path);
        if (!file_exists($videoPath)) {
            throw new Exception("Video file not found: {$videoPath}");
        }
        
        // Set defaults - simplified as requested
        $title = $title ?? $track->title;
        $description = $description ?? $track->title;
        
        // Prepare tags - Remove sunopanel references
        $tags = [];
        if (!empty($track->genres_string)) {
            $tags = array_map('trim', explode(',', $track->genres_string));
        }
        $tags = array_merge($tags, ['ai music', 'ai generated']);
        
        // Upload the video
        try {
            $videoId = $this->youtubeService->uploadVideo(
                $videoPath,
                $title,
                $description,
                $tags,
                $privacy,
                $madeForKids,
                $isShort
            );
            
            if (!$videoId) {
                throw new Exception('Failed to upload video to YouTube');
            }
            
            // Update the track with YouTube info
            $track->youtube_video_id = $videoId;
            $track->youtube_uploaded_at = now();
            $track->save();
            
            // Add to genre-based playlists if requested and not a Short
            // (Shorts typically don't go into playlists)
            if ($addToPlaylist && !$isShort && !empty($track->genres_string)) {
                $genres = explode(',', $track->genres_string);
                foreach ($genres as $genre) {
                    $genre = trim($genre);
                    if (!empty($genre)) {
                        // Removed "SunoPanel" from playlist name
                        $playlistName = $genre;
                        $this->addToPlaylist($videoId, $playlistName, $track);
                    }
                }
            }
            
            return $videoId;
        } catch (Exception $e) {
            Log::error('YouTube upload failed: ' . $e->getMessage(), [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Add a video to a playlist
     * 
     * @param string $videoId
     * @param string $playlistName
     * @param Track|null $track
     * @return bool
     */
    protected function addToPlaylist(string $videoId, string $playlistName, ?Track $track = null): bool
    {
        try {
            $playlistId = $this->youtubeService->findOrCreatePlaylist(
                $playlistName,
                "AI Generated Music - {$playlistName}", // Removed SunoPanel reference
                'public'
            );
            
            if (!$playlistId) {
                Log::warning("Could not find or create playlist: {$playlistName}");
                return false;
            }
            
            $success = $this->youtubeService->addVideoToPlaylist($videoId, $playlistId);
            
            if ($success && $track) {
                $track->youtube_playlist_id = $playlistId;
                $track->save();
            }
            
            return $success;
        } catch (\Exception $e) {
            Log::error("Failed to add video to playlist: " . $e->getMessage(), [
                'playlist' => $playlistName,
                'video_id' => $videoId,
                'exception' => $e,
            ]);
            
            return false;
        }
    }

    /**
     * Set a custom YouTube service instance
     * 
     * @param \Google_Service_YouTube|YouTubeService $service
     * @return void
     */
    public function setYoutubeService($service): void
    {
        if ($service instanceof \Google_Service_YouTube) {
            // If using the Google API client directly, wrap it in our service
            if ($this->youtubeService instanceof YouTubeService) {
                $this->youtubeService->setGoogleService($service);
            }
        } elseif ($service instanceof YouTubeService) {
            $this->youtubeService = $service;
        } else {
            throw new \InvalidArgumentException('Invalid YouTube service type');
        }
    }
} 