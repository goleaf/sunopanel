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
use Symfony\Component\Process\Process;
use Exception;

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
     * @param string $videoPath Path to the video file
     * @param string $email YouTube/Google account email
     * @param string $password YouTube/Google account password
     * @param string $title Video title
     * @param string $description Video description
     * @param string $tags Video tags (comma separated)
     * @param string $privacy Privacy setting (public, unlisted, private)
     * @param string $category Video category
     * @return array The result of the upload process
     */
    public function uploadVideo(
        string $videoPath,
        string $email,
        string $password,
        string $title,
        string $description = '',
        string $tags = '',
        string $privacy = 'unlisted',
        string $category = 'Music'
    ): array {
        // First, generate the client secrets file
        $this->generateClientSecrets();
        
        // Check if the video file exists
        if (!file_exists($videoPath)) {
            throw new Exception("Video file not found: {$videoPath}");
        }
        
        // Prepare the upload command
        $command = [
            base_path('vendor/bin/youtube-direct-upload'),
            '--email', $email,
            '--password', $password,
            '--title', $title,
            '--description', $description,
            '--tags', $tags,
            '--privacy', $privacy,
            '--category', $category,
            $videoPath
        ];
        
        // Execute the command
        $process = new Process($command);
        $process->setTimeout(3600); // 1 hour timeout for large uploads
        $process->run();
        
        // Get output and error
        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();
        
        // Log the process output
        Log::info('YouTube upload output', [
            'output' => $output,
            'error' => $errorOutput
        ]);
        
        // Check if upload was successful
        if (!$process->isSuccessful()) {
            Log::error('YouTube upload failed', [
                'command' => implode(' ', $command),
                'exit_code' => $process->getExitCode(),
                'output' => $output,
                'error' => $errorOutput
            ]);
            
            return [
                'success' => false,
                'message' => 'Upload failed: ' . $errorOutput,
                'output' => $output
            ];
        }
        
        // Extract video ID if available
        $videoId = null;
        if (preg_match('/Video ID: ([A-Za-z0-9_-]+)/', $output, $matches)) {
            $videoId = $matches[1];
        }
        
        return [
            'success' => true,
            'message' => 'Upload succeeded',
            'video_id' => $videoId,
            'output' => $output
        ];
    }
    
    /**
     * Generate client secrets JSON file from environment variables
     *
     * @return string Path to the generated file
     */
    private function generateClientSecrets(): string
    {
        $process = new Process([base_path('vendor/bin/youtube-client-secrets')]);
        $process->run();
        
        if (!$process->isSuccessful()) {
            throw new Exception('Failed to generate client secrets: ' . $process->getErrorOutput());
        }
        
        return '/tmp/client_secrets.json';
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
            '',
            '',
            $title,
            $description,
            implode(',', $tags),
            $privacyStatus ?? 'unlisted',
            $categoryId ?? 'Music'
        );
        
        if (!$videoId['success']) {
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
            return ['video_id' => $videoId['video_id'], 'playlist_id' => null];
        }
        
        // Add the video to the playlist
        $success = $this->addVideoToPlaylist($videoId['video_id'], $playlistId);
        
        if (!$success) {
            Log::error("Failed to add video {$videoId['video_id']} to playlist {$playlistId}");
            return ['video_id' => $videoId['video_id'], 'playlist_id' => $playlistId];
        }
        
        return [
            'video_id' => $videoId['video_id'],
            'playlist_id' => $playlistId,
            'success' => true,
        ];
    }
} 