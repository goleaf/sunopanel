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
     * @return string|null The YouTube video ID if successful
     * @throws Exception If upload fails
     */
    public function uploadTrack(
        Track $track,
        ?string $title = null,
        ?string $description = null,
        string $privacy = 'unlisted',
        bool $addToPlaylist = true
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
        
        // Set defaults
        $title = $title ?? $track->title;
        $description = $description ?? "Generated with SunoPanel\nTrack: {$track->title}";
        
        // Add genre information if available
        if (!empty($track->genres_string)) {
            $description .= "\nGenres: {$track->genres_string}";
        }
        
        // Prepare tags
        $tags = [];
        if (!empty($track->genres_string)) {
            $tags = array_map('trim', explode(',', $track->genres_string));
        }
        $tags = array_merge($tags, ['sunopanel', 'ai music', 'ai generated']);
        
        // Upload the video
        $videoId = $this->youtubeService->uploadVideo(
            $videoPath,
            $title,
            $description,
            $tags,
            $privacy
        );
        
        if (!$videoId) {
            throw new Exception('Failed to upload video to YouTube');
        }
        
        // Update the track with YouTube info
        $track->youtube_video_id = $videoId;
        $track->youtube_uploaded_at = now();
        $track->save();
        
        // Add to genre-based playlists if requested
        if ($addToPlaylist && $track->genres->isNotEmpty()) {
            foreach ($track->genres as $genre) {
                $playlistName = "SunoPanel - {$genre->name}";
                $this->addToPlaylist($videoId, $playlistName, $track);
            }
        }
        
        return $videoId;
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
                "SunoPanel playlist - {$playlistName}",
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
} 