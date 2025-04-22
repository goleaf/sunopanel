<?php

namespace App\Services;

use Google_Client;
use Google_Service_YouTube;
use Google_Service_YouTube_Video;
use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;
use Google_Service_YouTube_PlaylistItem;
use Google_Service_YouTube_PlaylistItemSnippet;
use Google_Service_YouTube_ResourceId;
use Google_Http_MediaFileUpload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Exception;

final class YouTubeUploader
{
    private Google_Client $client;
    private Google_Service_YouTube $youtube;
    private bool $isAuthenticated = false;

    /**
     * Constructor initializes the Google client.
     */
    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName(Config::get('youtube.application_name', 'SunoPanel'));
        $this->client->setScopes(Config::get('youtube.scopes'));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        
        // Set the client ID and secret from config
        $this->client->setClientId(Config::get('youtube.client_id'));
        $this->client->setClientSecret(Config::get('youtube.client_secret'));
        $this->client->setRedirectUri(Config::get('youtube.redirect_uri'));
        
        // Check if we have tokens in config
        $accessToken = Config::get('youtube.access_token');
        $refreshToken = Config::get('youtube.refresh_token');
        $tokenExpiresAt = Config::get('youtube.token_expires_at');
        
        if ($accessToken && $refreshToken) {
            // Set the access token
            $this->client->setAccessToken([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in' => $tokenExpiresAt ? ($tokenExpiresAt - time()) : 3600,
            ]);
            
            // Refresh the token if it's expired
            if ($this->client->isAccessTokenExpired()) {
                try {
                    $this->refreshToken();
                } catch (Exception $e) {
                    Log::error('Failed to refresh YouTube token: ' . $e->getMessage());
                }
            }
            
            $this->youtube = new Google_Service_YouTube($this->client);
            $this->isAuthenticated = true;
        }
    }
    
    /**
     * Refresh the OAuth token
     * 
     * @return bool True if token was refreshed successfully
     */
    public function refreshToken(): bool
    {
        try {
            $this->client->refreshToken($this->client->getRefreshToken());
            $newToken = $this->client->getAccessToken();
            
            // Store this in your environment or database for future use
            Log::info('Token refreshed. New values:', [
                'access_token' => $newToken['access_token'],
                'expires_in' => $newToken['expires_in'],
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('Failed to refresh token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the authentication URL to redirect the user to
     * 
     * @return string OAuth authorization URL
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }
    
    /**
     * Handle the OAuth callback and get tokens
     * 
     * @param string $authCode The authorization code from Google
     * @return array|false Token data or false if unsuccessful
     */
    public function handleAuthCallback(string $authCode)
    {
        try {
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);
            $this->client->setAccessToken($accessToken);
            
            if (array_key_exists('error', $accessToken)) {
                Log::error('Authentication error: ' . $accessToken['error']);
                return false;
            }
            
            $this->youtube = new Google_Service_YouTube($this->client);
            $this->isAuthenticated = true;
            
            return [
                'access_token' => $accessToken['access_token'],
                'refresh_token' => $accessToken['refresh_token'] ?? null,
                'expires_in' => $accessToken['expires_in'],
                'expires_at' => time() + $accessToken['expires_in'],
            ];
        } catch (Exception $e) {
            Log::error('Failed to handle auth callback: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Upload a video to YouTube
     *
     * @param string $videoPath Path to the video file
     * @param string $title Video title
     * @param string $description Video description
     * @param array $tags Video tags
     * @param string $privacyStatus Privacy status (public, unlisted, private)
     * @param string $categoryId Video category ID
     * @return string|null YouTube video ID if successful, null if failed
     */
    public function upload(
        string $videoPath,
        string $title,
        string $description = '',
        array $tags = [],
        string $privacyStatus = 'unlisted',
        string $categoryId = '10'
    ): ?string {
        if (!$this->isAuthenticated) {
            Log::error('YouTube uploader is not authenticated');
            return null;
        }
        
        if (!file_exists($videoPath)) {
            Log::error("Video file not found: {$videoPath}");
            return null;
        }
        
        try {
            // Create a snippet with video metadata
            $snippet = new Google_Service_YouTube_VideoSnippet();
            $snippet->setTitle($title);
            $snippet->setDescription($description);
            $snippet->setTags($tags);
            $snippet->setCategoryId($categoryId);
            
            // Set the privacy status
            $status = new Google_Service_YouTube_VideoStatus();
            $status->setPrivacyStatus($privacyStatus);
            
            // Create video object
            $video = new Google_Service_YouTube_Video();
            $video->setSnippet($snippet);
            $video->setStatus($status);
            
            // Set the chunk size (in bytes)
            $chunkSize = 1 * 1024 * 1024; // 1MB
            
            // Set the defer to prepare for file upload
            $this->client->setDefer(true);
            
            // Create the upload request
            $insertRequest = $this->youtube->videos->insert('snippet,status', $video);
            
            // Create upload specifics
            $media = new Google_Http_MediaFileUpload(
                $this->client,
                $insertRequest,
                mime_content_type($videoPath),
                null,
                true,
                $chunkSize
            );
            
            // Set the file size
            $media->setFileSize(filesize($videoPath));
            
            // Open the file for reading
            $status = false;
            $handle = fopen($videoPath, 'rb');
            
            // Start timer for logging upload speed
            $startTime = microtime(true);
            $uploadedSize = 0;
            
            // Upload the file in chunks
            while (!$status && !feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                $status = $media->nextChunk($chunk);
                
                // Log progress
                $uploadedSize += strlen($chunk);
                $currentTime = microtime(true);
                $elapsedTime = $currentTime - $startTime;
                $uploadSpeed = ($elapsedTime > 0) ? ($uploadedSize / $elapsedTime) : 0;
                
                Log::info('Uploading video', [
                    'uploaded' => $this->formatBytes($uploadedSize),
                    'total' => $this->formatBytes(filesize($videoPath)),
                    'progress' => round(($uploadedSize / filesize($videoPath)) * 100, 2) . '%',
                    'speed' => $this->formatBytes($uploadSpeed) . '/s',
                ]);
            }
            
            // Close the file
            fclose($handle);
            
            // Reset defer
            $this->client->setDefer(false);
            
            // If upload succeeded, return the video ID
            if ($status) {
                $videoId = $status['id'];
                Log::info("Successfully uploaded video with ID: {$videoId}");
                return $videoId;
            }
        } catch (Exception $e) {
            Log::error('Failed to upload video: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Add a video to a YouTube playlist
     *
     * @param string $videoId YouTube video ID
     * @param string $playlistId YouTube playlist ID
     * @return string|null Playlist item ID if successful, null if failed
     */
    public function addToPlaylist(string $videoId, string $playlistId): ?string
    {
        if (!$this->isAuthenticated) {
            Log::error('YouTube uploader is not authenticated');
            return null;
        }
        
        try {
            // Create a resource ID for the video
            $resourceId = new Google_Service_YouTube_ResourceId();
            $resourceId->setKind('youtube#video');
            $resourceId->setVideoId($videoId);
            
            // Create a snippet with playlist item metadata
            $snippet = new Google_Service_YouTube_PlaylistItemSnippet();
            $snippet->setPlaylistId($playlistId);
            $snippet->setResourceId($resourceId);
            
            // Create the playlist item
            $playlistItem = new Google_Service_YouTube_PlaylistItem();
            $playlistItem->setSnippet($snippet);
            
            // Add the video to the playlist
            $response = $this->youtube->playlistItems->insert('snippet', $playlistItem);
            
            if ($response && isset($response['id'])) {
                Log::info("Added video {$videoId} to playlist {$playlistId}");
                return $response['id'];
            }
        } catch (Exception $e) {
            Log::error('Failed to add video to playlist: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Format bytes to human-readable format
     *
     * @param int $bytes Bytes to format
     * @param int $precision Decimal precision
     * @return string Formatted size
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    public function getService(): Google_Service_YouTube
    {
        if (!$this->isAuthenticated) {
            throw new Exception('YouTube uploader is not authenticated');
        }
        
        return $this->youtube;
    }
    
    /**
     * Check if authenticated with YouTube
     *
     * @return bool True if authenticated, false otherwise
     */
    public function isAuthenticated(): bool
    {
        return $this->isAuthenticated;
    }
} 