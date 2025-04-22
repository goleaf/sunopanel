<?php

namespace App\Services;

use Exception;
use Google_Client;
use Google_Service_YouTube;
use Google_Service_YouTube_Video;
use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;
use Google_Http_MediaFileUpload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class YouTubeApiService
{
    /**
     * @var Google_Client
     */
    protected $client;

    /**
     * @var Google_Service_YouTube
     */
    protected $youtube;

    /**
     * Create a new YouTube API Service instance.
     */
    public function __construct()
    {
        $this->client = $this->getClient();
        
        if ($this->client->getAccessToken()) {
            $this->youtube = new Google_Service_YouTube($this->client);
        }
    }

    /**
     * Get configured Google Client.
     *
     * @return Google_Client
     */
    protected function getClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('SunoPanel YouTube Uploader');
        $client->setScopes([
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube',
        ]);
        
        // Load credentials from config
        $clientId = config('youtube.client_id');
        $clientSecret = config('youtube.client_secret');
        $redirectUri = config('youtube.redirect_uri', 'https://sunopanel.prus.dev/youtube-auth');
        
        if (empty($clientId) || empty($clientSecret)) {
            Log::warning('YouTube API credentials not configured. OAuth will not work properly.');
        }
        
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // Force to ask for consent to get refresh token
        $client->setIncludeGrantedScopes(true);
        
        // Check for stored access token
        if (config('youtube.access_token')) {
            $accessToken = [
                'access_token' => config('youtube.access_token'),
                'refresh_token' => config('youtube.refresh_token'),
                'created' => config('youtube.token_created_at', time()),
                'expires_in' => config('youtube.token_expires_in', 3600),
            ];
            
            $client->setAccessToken($accessToken);
            
            // Refresh the token if it's expired
            if ($client->isAccessTokenExpired()) {
                try {
                    $refreshToken = $client->getRefreshToken();
                    if ($refreshToken) {
                        $client->fetchAccessTokenWithRefreshToken($refreshToken);
                        
                        // Save new access token
                        $newToken = $client->getAccessToken();
                        $this->saveAccessToken($newToken);
                    } else {
                        Log::warning('YouTube refresh token not available. User may need to re-authenticate.');
                    }
                } catch (Exception $e) {
                    Log::error('Failed to refresh YouTube access token: ' . $e->getMessage());
                }
            }
        }
        
        return $client;
    }
    
    /**
     * Save access token to config
     *
     * @param array $accessToken
     * @return void
     */
    public function saveAccessToken(array $accessToken): void
    {
        // This would typically update your database or config
        // For this example, we'll use the .env file updater helper
        $this->updateEnvVariable('YOUTUBE_ACCESS_TOKEN', $accessToken['access_token'] ?? '');
        
        if (isset($accessToken['refresh_token'])) {
            $this->updateEnvVariable('YOUTUBE_REFRESH_TOKEN', $accessToken['refresh_token']);
        }
        
        if (isset($accessToken['created'])) {
            $this->updateEnvVariable('YOUTUBE_TOKEN_CREATED_AT', $accessToken['created']);
        }
        
        if (isset($accessToken['expires_in'])) {
            $this->updateEnvVariable('YOUTUBE_TOKEN_EXPIRES_IN', $accessToken['expires_in']);
        }
    }
    
    /**
     * Update .env variable
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    protected function updateEnvVariable(string $key, string $value): void
    {
        $path = base_path('.env');
        
        if (file_exists($path)) {
            $content = file_get_contents($path);
            
            // If the key exists, replace it
            if (strpos($content, $key . '=') !== false) {
                $content = preg_replace("/^{$key}=.*$/m", "{$key}=\"{$value}\"", $content);
            } else {
                // Otherwise, append it to the end of the file
                $content .= "\n{$key}=\"{$value}\"";
            }
            
            file_put_contents($path, $content);
        }
    }
    
    /**
     * Get authorization URL for OAuth flow
     *
     * @return string
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }
    
    /**
     * Exchange authorization code for access token
     *
     * @param string $code
     * @return array
     */
    public function fetchAccessTokenWithAuthCode(string $code): array
    {
        try {
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
            $this->client->setAccessToken($accessToken);
            $this->saveAccessToken($accessToken);
            $this->youtube = new Google_Service_YouTube($this->client);
            
            return $accessToken;
        } catch (Exception $e) {
            Log::error('Failed to fetch access token: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if the client is authenticated
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->client->getAccessToken() !== null && !$this->client->isAccessTokenExpired();
    }
    
    /**
     * Upload a video to YouTube
     *
     * @param string $videoPath
     * @param string $title
     * @param string $description
     * @param array $tags
     * @param string $privacyStatus
     * @param string $categoryId
     * @return string|null Video ID if successful
     * @throws Exception
     */
    public function uploadVideo(
        string $videoPath,
        string $title,
        string $description = '',
        array $tags = [],
        string $privacyStatus = 'unlisted',
        string $categoryId = '10'
    ): ?string {
        if (!$this->isAuthenticated()) {
            Log::error('YouTube API not authenticated. Please authenticate first.');
            throw new Exception('YouTube API not authenticated. Please authenticate first.');
        }
        
        if (!file_exists($videoPath)) {
            Log::error("Video file not found: {$videoPath}");
            throw new Exception("Video file not found: {$videoPath}");
        }
        
        try {
            // Create a snippet with title, description, tags, and category ID
            $snippet = new Google_Service_YouTube_VideoSnippet();
            $snippet->setTitle($title);
            $snippet->setDescription($description);
            $snippet->setTags($tags);
            $snippet->setCategoryId($categoryId);
            
            // Set the privacy status
            $status = new Google_Service_YouTube_VideoStatus();
            $status->setPrivacyStatus($privacyStatus);
            
            // Create the video resource
            $video = new Google_Service_YouTube_Video();
            $video->setSnippet($snippet);
            $video->setStatus($status);
            
            // Set the chunk size for uploading
            $chunkSize = 1 * 1024 * 1024; // 1MB
            
            // Set the defer to prepare for file upload
            $this->client->setDefer(true);
            
            // Create a request for the API's videos.insert method
            $insertRequest = $this->youtube->videos->insert(
                'snippet,status',
                $video
            );
            
            // Create a MediaFileUpload object for resumable uploads
            $media = new Google_Http_MediaFileUpload(
                $this->client,
                $insertRequest,
                'video/*',
                null,
                true,
                $chunkSize
            );
            
            // Set the file size
            $fileSize = filesize($videoPath);
            $media->setFileSize($fileSize);
            
            // Log the start of the upload
            Log::info("Starting YouTube upload for {$videoPath} (size: {$fileSize} bytes)");
            
            // Upload the file in chunks
            $status = false;
            $handle = fopen($videoPath, 'rb');
            
            while (!$status && !feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                $status = $media->nextChunk($chunk);
            }
            
            fclose($handle);
            
            // Reset the defer setting
            $this->client->setDefer(false);
            
            if ($status) {
                Log::info("Video uploaded successfully! Video ID: {$status['id']}");
                return $status['id'];
            }
            
            Log::error('Failed to upload video: Unknown error');
            return null;
            
        } catch (Exception $e) {
            Log::error('Exception during YouTube upload: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Add a video to a playlist
     *
     * @param string $videoId
     * @param string $playlistId
     * @return bool
     */
    public function addVideoToPlaylist(string $videoId, string $playlistId): bool
    {
        if (!$this->isAuthenticated()) {
            Log::error('YouTube API not authenticated. Please authenticate first.');
            return false;
        }
        
        try {
            // Create a resource ID for the video
            $resourceId = new \Google_Service_YouTube_ResourceId();
            $resourceId->setKind('youtube#video');
            $resourceId->setVideoId($videoId);
            
            // Create a snippet for the playlist item
            $playlistItemSnippet = new \Google_Service_YouTube_PlaylistItemSnippet();
            $playlistItemSnippet->setPlaylistId($playlistId);
            $playlistItemSnippet->setResourceId($resourceId);
            
            // Create the playlist item
            $playlistItem = new \Google_Service_YouTube_PlaylistItem();
            $playlistItem->setSnippet($playlistItemSnippet);
            
            // Add the video to the playlist
            $this->youtube->playlistItems->insert('snippet', $playlistItem);
            
            Log::info("Video {$videoId} added to playlist {$playlistId}");
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to add video to playlist: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find or create a playlist
     *
     * @param string $title
     * @param string $description
     * @param string $privacyStatus
     * @return string|null Playlist ID if found or created successfully
     */
    public function findOrCreatePlaylist(
        string $title,
        string $description = '',
        string $privacyStatus = 'public'
    ): ?string {
        if (!$this->isAuthenticated()) {
            Log::error('YouTube API not authenticated. Please authenticate first.');
            return null;
        }
        
        try {
            // First, try to find an existing playlist with the same title
            $playlists = $this->youtube->playlists->listPlaylists('snippet', [
                'mine' => true,
            ]);
            
            foreach ($playlists->getItems() as $playlist) {
                if ($playlist->getSnippet()->getTitle() === $title) {
                    return $playlist->getId();
                }
            }
            
            // If not found, create a new playlist
            $playlistSnippet = new \Google_Service_YouTube_PlaylistSnippet();
            $playlistSnippet->setTitle($title);
            $playlistSnippet->setDescription($description);
            
            $playlistStatus = new \Google_Service_YouTube_PlaylistStatus();
            $playlistStatus->setPrivacyStatus($privacyStatus);
            
            $playlist = new \Google_Service_YouTube_Playlist();
            $playlist->setSnippet($playlistSnippet);
            $playlist->setStatus($playlistStatus);
            
            $response = $this->youtube->playlists->insert('snippet,status', $playlist);
            
            Log::info("Created new playlist: {$title} (ID: {$response->getId()})");
            return $response->getId();
            
        } catch (Exception $e) {
            Log::error('Failed to find or create playlist: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all playlists for the authenticated user
     *
     * @return array Associative array of playlist ID => playlist title
     */
    public function getPlaylists(): array
    {
        if (!$this->isAuthenticated()) {
            Log::error('YouTube API not authenticated. Please authenticate first.');
            return [];
        }
        
        try {
            $playlists = $this->youtube->playlists->listPlaylists('snippet', [
                'mine' => true,
                'maxResults' => 50,
            ]);
            
            $result = [];
            foreach ($playlists->getItems() as $playlist) {
                $result[$playlist->getId()] = $playlist->getSnippet()->getTitle();
            }
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Failed to get playlists: ' . $e->getMessage());
            return [];
        }
    }
} 