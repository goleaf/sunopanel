<?php

namespace App\Services;

use Google_Client;
use Google_Service_YouTube;
use Google_Service_YouTube_Playlist;
use Google_Service_YouTube_PlaylistItem;
use Google_Service_YouTube_PlaylistItemSnippet;
use Google_Service_YouTube_PlaylistSnippet;
use Google_Service_YouTube_ResourceId;
use Google_Service_YouTube_Video;
use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class YouTubeService
{
    protected Google_Client $client;
    protected Google_Service_YouTube $youtube;
    
    public function __construct()
    {
        $this->client = app('google-client');
        $this->youtube = app('youtube');
    }
    
    /**
     * Check if the user is authenticated with YouTube
     */
    public function isAuthenticated(): bool
    {
        return (bool) $this->client->getAccessToken() && !$this->client->isAccessTokenExpired();
    }
    
    /**
     * Get the authorization URL for YouTube
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }
    
    /**
     * Handle the callback from YouTube OAuth
     */
    public function handleAuthCallback(string $authCode): bool
    {
        try {
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);
            $this->client->setAccessToken($accessToken);
            
            return true;
        } catch (\Exception $e) {
            Log::error('YouTube auth callback error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Upload a video to YouTube
     * 
     * @param string $filePath Local file path to the video
     * @param string $title Title of the video
     * @param string $description Description of the video
     * @param array $tags Tags for the video
     * @param string $privacyStatus Privacy status (public, unlisted, private)
     * @param int $categoryId YouTube category ID
     * @return string|null YouTube video ID if successful, null if failed
     */
    public function uploadVideo(
        string $filePath,
        string $title,
        string $description,
        array $tags = [],
        string $privacyStatus = null,
        int $categoryId = null
    ): ?string {
        if (!$this->isAuthenticated()) {
            Log::error('Cannot upload video: Not authenticated with YouTube');
            return null;
        }
        
        if (!file_exists($filePath)) {
            Log::error("Cannot upload video: File does not exist at {$filePath}");
            return null;
        }
        
        try {
            // Create a snippet with title, description, tags, and category ID
            $snippet = new Google_Service_YouTube_VideoSnippet();
            $snippet->setTitle($title);
            $snippet->setDescription($description);
            $snippet->setTags($tags);
            $snippet->setCategoryId($categoryId ?? config('youtube.default_category_id'));
            
            // Set the privacy status
            $status = new Google_Service_YouTube_VideoStatus();
            $status->setPrivacyStatus($privacyStatus ?? config('youtube.default_privacy_status'));
            
            // Create the video resource
            $video = new Google_Service_YouTube_Video();
            $video->setSnippet($snippet);
            $video->setStatus($status);
            
            // Set the chunk size to 50MB to reduce memory usage
            $this->client->setDefer(true);
            
            // Create the insert request
            $insertRequest = $this->youtube->videos->insert(
                'snippet,status',
                $video
            );
            
            // Create an upload MediaFileUpload object
            $media = $this->client->getHttpClient()->mediaFileUpload(
                $insertRequest->buildUri(),
                file_get_contents($filePath),
                'video/*',
                null
            );
            
            // Upload the file chunk by chunk
            $status = false;
            $handle = fopen($filePath, 'rb');
            while (!$status && !feof($handle)) {
                $chunk = fread($handle, 1024 * 1024 * 5); // 5MB chunks
                $status = $media->nextChunk($chunk);
            }
            fclose($handle);
            
            // Reset the defer flag
            $this->client->setDefer(false);
            
            if ($status) {
                Log::info("Video uploaded successfully: {$status['id']}");
                return $status['id'];
            }
        } catch (\Exception $e) {
            Log::error('YouTube upload error: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Get all playlists for the authenticated user
     * 
     * @return array Associative array of playlist ID => playlist title
     */
    public function getPlaylists(): array
    {
        if (!$this->isAuthenticated()) {
            Log::error('Cannot get playlists: Not authenticated with YouTube');
            return [];
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
            
            return $playlists;
        } catch (\Exception $e) {
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
        if (!$this->isAuthenticated()) {
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
            
            Log::info("Playlist created successfully: {$response->getId()}");
            return $response->getId();
        } catch (\Exception $e) {
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
        if (!$this->isAuthenticated()) {
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
        } catch (\Exception $e) {
            Log::error('YouTube add video to playlist error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Upload a video to YouTube and add it to a playlist
     * 
     * @param string $filePath Local file path to the video
     * @param string $title Title of the video
     * @param string $description Description of the video
     * @param string $playlistTitle Title of the playlist (will be created if it doesn't exist)
     * @param array $tags Tags for the video
     * @param string $privacyStatus Privacy status (public, unlisted, private)
     * @param int $categoryId YouTube category ID
     * @return array|null Array with video_id and playlist_id if successful, null if failed
     */
    public function uploadVideoToPlaylist(
        string $filePath,
        string $title,
        string $description,
        string $playlistTitle,
        array $tags = [],
        string $privacyStatus = null,
        int $categoryId = null
    ): ?array {
        // Upload the video
        $videoId = $this->uploadVideo(
            $filePath,
            $title,
            $description,
            $tags,
            $privacyStatus,
            $categoryId
        );
        
        if (!$videoId) {
            return null;
        }
        
        // Find or create the playlist
        $playlistId = $this->findOrCreatePlaylist(
            $playlistTitle,
            "SunoPanel generated playlist for {$playlistTitle}",
            $privacyStatus ?? 'public'
        );
        
        if (!$playlistId) {
            Log::error("Failed to find or create playlist: {$playlistTitle}");
            return ['video_id' => $videoId, 'playlist_id' => null];
        }
        
        // Add the video to the playlist
        $success = $this->addVideoToPlaylist($videoId, $playlistId);
        
        if (!$success) {
            Log::error("Failed to add video {$videoId} to playlist {$playlistId}");
            return ['video_id' => $videoId, 'playlist_id' => $playlistId];
        }
        
        return [
            'video_id' => $videoId,
            'playlist_id' => $playlistId,
            'success' => true,
        ];
    }
} 