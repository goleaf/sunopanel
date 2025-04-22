<?php

namespace App\Services;

use App\Models\YouTubeCredential;
use Exception;
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
use Google_Http_MediaFileUpload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class YouTubeService
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
     * @var YouTubeCredential
     */
    protected $credential;

    /**
     * Create a new YouTube Service instance.
     */
    public function __construct()
    {
        try {
            $this->credential = YouTubeCredential::getLatest();
        } catch (\Exception $e) {
            // Table might not exist yet
            $this->credential = null;
            \Log::warning('YouTube credentials table does not exist or has an error: ' . $e->getMessage());
        }
        
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
        
        if (!$this->credential) {
            Log::warning('No YouTube credentials found in database');
            return $client;
        }
        
        // Set OAuth credentials
        $client->setClientId($this->credential->client_id);
        $client->setClientSecret($this->credential->client_secret);
        $client->setRedirectUri($this->credential->redirect_uri);
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // Force to ask for consent to get refresh token
        $client->setIncludeGrantedScopes(true);
        
        // Check for stored access token
        if ($this->credential->access_token) {
            $accessToken = [
                'access_token' => $this->credential->access_token,
                'refresh_token' => $this->credential->refresh_token,
                'created' => $this->credential->token_created_at,
                'expires_in' => $this->credential->token_expires_in,
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
     * Save access token to database
     *
     * @param array $accessToken
     * @return void
     */
    public function saveAccessToken(array $accessToken): void
    {
        if (!$this->credential) {
            $this->credential = new YouTubeCredential();
        }
        
        $this->credential->access_token = $accessToken['access_token'] ?? null;
        
        if (isset($accessToken['refresh_token'])) {
            $this->credential->refresh_token = $accessToken['refresh_token'];
        }
        
        if (isset($accessToken['created'])) {
            $this->credential->token_created_at = $accessToken['created'];
        }
        
        if (isset($accessToken['expires_in'])) {
            $this->credential->token_expires_in = $accessToken['expires_in'];
        }
        
        $this->credential->save();
    }
    
    /**
     * Save client credentials to database
     * 
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUri
     * @return void
     */
    public function saveClientCredentials(string $clientId, string $clientSecret, string $redirectUri): void
    {
        if (!$this->credential) {
            $this->credential = new YouTubeCredential();
        }
        
        $this->credential->client_id = $clientId;
        $this->credential->client_secret = $clientSecret;
        $this->credential->redirect_uri = $redirectUri;
        $this->credential->use_oauth = true;
        $this->credential->save();
        
        // Refresh client after saving credentials
        $this->client = $this->getClient();
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
            Log::error('Authentication details:', [
                'has_credential' => !is_null($this->credential),
                'client_id_set' => !is_null($this->credential?->client_id),
                'has_access_token' => !is_null($this->client->getAccessToken()),
                'token_expired' => $this->client->isAccessTokenExpired(),
            ]);
            throw new Exception('YouTube API not authenticated. Please authenticate first.');
        }
        
        if (!file_exists($videoPath)) {
            Log::error("Video file not found: {$videoPath}");
            throw new Exception("Video file not found: {$videoPath}");
        }
        
        try {
            $fileSize = filesize($videoPath);
            Log::info("Starting YouTube upload for file: {$videoPath}");
            Log::info("File size: {$fileSize} bytes");
            Log::info("Title: {$title}");
            Log::info("Privacy status: {$privacyStatus}");
            
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
            $media->setFileSize($fileSize);
            
            // Log the start of the upload
            Log::info("Starting YouTube upload for {$videoPath} (size: {$fileSize} bytes)");
            
            // Upload the file in chunks
            $status = false;
            $handle = fopen($videoPath, 'rb');
            
            if (!$handle) {
                Log::error("Failed to open the file for reading: {$videoPath}");
                throw new Exception("Failed to open the file for reading: {$videoPath}");
            }
            
            $chunkNumber = 0;
            $uploadedBytes = 0;
            
            while (!$status && !feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                $chunkNumber++;
                $uploadedBytes += strlen($chunk);
                
                // Log progress periodically
                if ($chunkNumber % 10 === 0) {
                    $progress = ($uploadedBytes / $fileSize) * 100;
                    Log::info("Upload progress: " . number_format($progress, 2) . "% ({$uploadedBytes} / {$fileSize} bytes)");
                }
                
                try {
                    $status = $media->nextChunk($chunk);
                } catch (\Exception $e) {
                    Log::error("Error during chunk upload (chunk #{$chunkNumber}): " . $e->getMessage());
                    throw $e;
                }
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
            Log::error('Exception details: ', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
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