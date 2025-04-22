<?php

namespace App\Services;

use Google_Service_YouTube;
use Google_Service_YouTube_Playlist;
use Google_Service_YouTube_PlaylistSnippet;
use Google_Service_YouTube_PlaylistStatus;
use Google_Service_YouTube_PlaylistItem;
use Google_Service_YouTube_PlaylistItemSnippet;
use Google_Service_YouTube_ResourceId;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

final class YouTubePlaylistManager
{
    private YouTubeUploader $uploader;
    private Google_Service_YouTube $youtube;
    private bool $isAuthenticated = false;
    
    /**
     * Constructor initializes the YouTube service.
     */
    public function __construct(YouTubeUploader $uploader)
    {
        $this->uploader = $uploader;
        
        // Get YouTube service from uploader
        if (method_exists($this->uploader, 'getService')) {
            $this->youtube = $this->uploader->getService();
            $this->isAuthenticated = $this->uploader->isAuthenticated();
        }
    }
    
    /**
     * Get all playlists for the authenticated user
     * 
     * @param bool $forceRefresh Force refresh of cached playlists
     * @return array Associative array of playlist ID => playlist title
     */
    public function getPlaylists(bool $forceRefresh = false): array
    {
        if (!$this->isAuthenticated) {
            Log::error('Cannot get playlists: Not authenticated with YouTube');
            return [];
        }
        
        // Check cache first unless force refresh is requested
        $cacheKey = 'youtube_playlists';
        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey, []);
        }
        
        try {
            $playlists = [];
            $nextPageToken = null;
            
            do {
                $playlistsResponse = $this->youtube->playlists->listPlaylists(
                    'snippet',
                    [
                        'mine' => true,
                        'maxResults' => 50,
                        'pageToken' => $nextPageToken,
                    ]
                );
                
                foreach ($playlistsResponse->getItems() as $playlist) {
                    $playlists[$playlist->getId()] = $playlist->getSnippet()->getTitle();
                }
                
                $nextPageToken = $playlistsResponse->getNextPageToken();
            } while ($nextPageToken);
            
            // Cache the results for 15 minutes
            Cache::put($cacheKey, $playlists, 900);
            
            return $playlists;
        } catch (Exception $e) {
            Log::error('YouTube get playlists error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create a new playlist
     * 
     * @param string $title Title of the playlist
     * @param string $description Description of the playlist
     * @param string $privacyStatus Privacy status (public, unlisted, private)
     * @return string|null Playlist ID if successful, null if failed
     */
    public function createPlaylist(
        string $title,
        string $description = '',
        string $privacyStatus = 'public'
    ): ?string {
        if (!$this->isAuthenticated) {
            Log::error('Cannot create playlist: Not authenticated with YouTube');
            return null;
        }
        
        try {
            // Create a snippet with title and description
            $snippet = new Google_Service_YouTube_PlaylistSnippet();
            $snippet->setTitle($title);
            $snippet->setDescription($description);
            
            // Create a status with privacy status
            $status = new Google_Service_YouTube_PlaylistStatus();
            $status->setPrivacyStatus($privacyStatus);
            
            // Create the playlist resource
            $playlist = new Google_Service_YouTube_Playlist();
            $playlist->setSnippet($snippet);
            $playlist->setStatus($status);
            
            // Create the playlist
            $response = $this->youtube->playlists->insert(
                'snippet,status',
                $playlist
            );
            
            // Clear the playlists cache
            Cache::forget('youtube_playlists');
            
            Log::info("Playlist created successfully: {$response->getId()}");
            return $response->getId();
        } catch (Exception $e) {
            Log::error('YouTube create playlist error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find a playlist by title
     * 
     * @param string $title Title of the playlist to find
     * @return string|null Playlist ID if found, null if not found
     */
    public function findPlaylistByTitle(string $title): ?string
    {
        $playlists = $this->getPlaylists();
        
        foreach ($playlists as $id => $playlistTitle) {
            if (strtolower($playlistTitle) === strtolower($title)) {
                return $id;
            }
        }
        
        return null;
    }
    
    /**
     * Find or create a playlist
     * 
     * @param string $title Title of the playlist
     * @param string $description Description of the playlist
     * @param string $privacyStatus Privacy status (public, unlisted, private)
     * @return string|null Playlist ID if successful, null if failed
     */
    public function findOrCreatePlaylist(
        string $title,
        string $description = '',
        string $privacyStatus = 'public'
    ): ?string {
        $playlistId = $this->findPlaylistByTitle($title);
        
        if ($playlistId) {
            return $playlistId;
        }
        
        return $this->createPlaylist($title, $description, $privacyStatus);
    }
    
    /**
     * Add a video to a playlist
     * 
     * @param string $videoId YouTube video ID
     * @param string $playlistId YouTube playlist ID
     * @return bool True if successful, false if failed
     */
    public function addVideoToPlaylist(string $videoId, string $playlistId): bool
    {
        if (!$this->isAuthenticated) {
            Log::error('Cannot add video to playlist: Not authenticated with YouTube');
            return false;
        }
        
        try {
            // Create a resource ID for the video
            $resourceId = new Google_Service_YouTube_ResourceId();
            $resourceId->setKind('youtube#video');
            $resourceId->setVideoId($videoId);
            
            // Create a snippet with playlist ID and resource ID
            $snippet = new Google_Service_YouTube_PlaylistItemSnippet();
            $snippet->setPlaylistId($playlistId);
            $snippet->setResourceId($resourceId);
            
            // Create the playlist item
            $playlistItem = new Google_Service_YouTube_PlaylistItem();
            $playlistItem->setSnippet($snippet);
            
            // Add the video to the playlist
            $response = $this->youtube->playlistItems->insert(
                'snippet',
                $playlistItem
            );
            
            Log::info("Video {$videoId} added to playlist {$playlistId}");
            return true;
        } catch (Exception $e) {
            Log::error('YouTube add video to playlist error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all videos in a playlist
     * 
     * @param string $playlistId YouTube playlist ID
     * @return array Array of video information
     */
    public function getPlaylistVideos(string $playlistId): array
    {
        if (!$this->isAuthenticated) {
            Log::error('Cannot get playlist videos: Not authenticated with YouTube');
            return [];
        }
        
        try {
            $videos = [];
            $nextPageToken = null;
            
            do {
                $playlistItemsResponse = $this->youtube->playlistItems->listPlaylistItems(
                    'snippet',
                    [
                        'playlistId' => $playlistId,
                        'maxResults' => 50,
                        'pageToken' => $nextPageToken,
                    ]
                );
                
                foreach ($playlistItemsResponse->getItems() as $item) {
                    $snippet = $item->getSnippet();
                    $videos[] = [
                        'id' => $item->getId(),
                        'video_id' => $snippet->getResourceId()->getVideoId(),
                        'title' => $snippet->getTitle(),
                        'description' => $snippet->getDescription(),
                        'published_at' => $snippet->getPublishedAt(),
                        'thumbnail' => $snippet->getThumbnails()->getDefault()->getUrl(),
                    ];
                }
                
                $nextPageToken = $playlistItemsResponse->getNextPageToken();
            } while ($nextPageToken);
            
            return $videos;
        } catch (Exception $e) {
            Log::error('YouTube get playlist videos error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Remove a video from a playlist
     * 
     * @param string $playlistItemId Playlist item ID
     * @return bool True if successful, false if failed
     */
    public function removeVideoFromPlaylist(string $playlistItemId): bool
    {
        if (!$this->isAuthenticated) {
            Log::error('Cannot remove video from playlist: Not authenticated with YouTube');
            return false;
        }
        
        try {
            $this->youtube->playlistItems->delete($playlistItemId);
            Log::info("Playlist item {$playlistItemId} removed successfully");
            return true;
        } catch (Exception $e) {
            Log::error('YouTube remove video from playlist error: ' . $e->getMessage());
            return false;
        }
    }
} 