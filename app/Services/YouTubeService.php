<?php

namespace App\Services;

use App\Models\YouTubeCredential;
use App\Models\YouTubeAccount;
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
     * @var string|null
     */
    protected $apiKey = null;

    /**
     * Create a new YouTube Service instance.
     */
    public function __construct()
    {
        try {
            // Initialize client first to ensure it's always available
            $this->client = $this->getClient();
            
            // Try to load the active account
            $activeAccount = \App\Models\YouTubeAccount::getActive();
            
            if ($activeAccount) {
                // Set the active account
                $this->setAccount($activeAccount);
            } else {
                // For backward compatibility, try legacy credentials
                try {
                    $this->credential = YouTubeCredential::getLatest();
                    // Re-initialize client with credentials
                    $this->client = $this->getClient();
                } catch (\Exception $e) {
                    // Table might not exist yet
                    $this->credential = null;
                    \Log::warning('YouTube credentials table does not exist or has an error: ' . $e->getMessage());
                }
            }
            
            // Initialize YouTube service if we have a valid token
            if ($this->client && $this->client->getAccessToken() && !$this->client->isAccessTokenExpired()) {
                $this->youtube = new Google_Service_YouTube($this->client);
            }
        } catch (\Exception $e) {
            \Log::error('Error initializing YouTube service: ' . $e->getMessage());
            // Ensure we always have a client, even if there's an error
            if (!$this->client) {
                try {
                    $this->client = new \Google_Client();
                    $this->client->setApplicationName('SunoPanel YouTube Uploader');
                    $this->client->setRedirectUri(url('/youtube-auth'));
                } catch (\Exception $clientError) {
                    \Log::error('Failed to create fallback Google Client: ' . $clientError->getMessage());
                }
            }
        }
    }

    /**
     * Set the API key for authentication
     *
     * @param string $apiKey
     * @return void
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
        $this->client->setDeveloperKey($apiKey);
        $this->youtube = new Google_Service_YouTube($this->client);
        
        Log::info('YouTube API key set');
    }

    /**
     * Get configured Google Client.
     *
     * @return Google_Client
     */
    protected function getClient(): Google_Client
    {
        try {
            $client = new Google_Client();
            $client->setApplicationName('SunoPanel YouTube Uploader');
            $client->setScopes([
                'https://www.googleapis.com/auth/youtube.upload',
                'https://www.googleapis.com/auth/youtube',
            ]);
            
            // Use API key if provided
            if ($this->apiKey) {
                $client->setDeveloperKey($this->apiKey);
                return $client;
            }
            
            // Try to get credentials from database
            if (!$this->credential) {
                try {
                    $this->credential = YouTubeCredential::getLatest();
                } catch (\Exception $e) {
                    Log::warning('Failed to load YouTube credentials: ' . $e->getMessage());
                }
            }
            
            if (!$this->credential) {
                Log::warning('No YouTube credentials found in database');
                // Set a default redirect URI to prevent the error
                $client->setRedirectUri(url('/youtube-auth'));
                return $client;
            }
            
            // Validate that we have the required OAuth credentials
            if (!$this->credential->hasValidAuthData()) {
                Log::error('YouTube credentials are incomplete', [
                    'has_client_id' => !empty($this->credential->client_id),
                    'has_client_secret' => !empty($this->credential->client_secret),
                    'has_redirect_uri' => !empty($this->credential->redirect_uri),
                    'use_oauth' => $this->credential->use_oauth,
                ]);
                // Set a default redirect URI to prevent the error
                $client->setRedirectUri(url('/youtube-auth'));
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
        } catch (\Exception $e) {
            Log::error('Error creating Google Client: ' . $e->getMessage());
            // Return a basic client as fallback
            $client = new Google_Client();
            $client->setApplicationName('SunoPanel YouTube Uploader');
            $client->setRedirectUri(url('/youtube-auth'));
            return $client;
        }
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
        // Ensure we have a redirect URI set
        if (!$this->client->getRedirectUri()) {
            $this->client->setRedirectUri(url('/youtube-auth'));
        }
        
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
            Log::error('Error fetching YouTube access token: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Handle the authorization callback and save the token
     *
     * @param string $code The authorization code from callback
     * @return bool Whether authentication was successful
     */
    public function handleAuthCallback(string $code): bool
    {
        try {
            $this->fetchAccessTokenWithAuthCode($code);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to handle YouTube auth callback: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if the client is authenticated
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        // If an API key is set, consider it authenticated for read operations only
        if ($this->apiKey) {
            return true; // Note: API keys can only be used for read operations, not uploads
        }
        
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
     * @param bool $madeForKids
     * @param bool $isShort
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
        bool $madeForKids = false,
        bool $isShort = false,
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
            Log::info("Made for kids: " . ($madeForKids ? 'Yes' : 'No'));
            Log::info("Is Short: " . ($isShort ? 'Yes' : 'No'));
            
            // Create a snippet with title, description, tags, and category ID
            $snippet = new Google_Service_YouTube_VideoSnippet();
            $snippet->setTitle($title);
            $snippet->setDescription($description);
            $snippet->setTags($tags);
            $snippet->setCategoryId($categoryId);
            
            // Set the privacy status and made for kids setting
            $status = new Google_Service_YouTube_VideoStatus();
            $status->setPrivacyStatus($privacyStatus);
            $status->setSelfDeclaredMadeForKids($madeForKids);
            
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

    /**
     * Set a Google_Service_YouTube instance directly
     *
     * @param Google_Service_YouTube $service
     * @return void
     */
    public function setGoogleService(Google_Service_YouTube $service): void
    {
        $this->youtube = $service;
    }

    /**
     * List videos uploaded by the authenticated user
     *
     * @param int $maxResults Maximum number of results to return
     * @return array Array of video data
     */
    public function listUploadedVideos(int $maxResults = 50): array
    {
        if (!$this->isAuthenticated()) {
            Log::error('YouTube API not authenticated. Please authenticate first.');
            return [];
        }
        
        try {
            // First, get the channel ID for the authenticated user
            $channelsResponse = $this->youtube->channels->listChannels('contentDetails', [
                'mine' => true,
            ]);
            
            if (empty($channelsResponse->getItems())) {
                Log::warning('No channel found for authenticated user');
                return [];
            }
            
            $channel = $channelsResponse->getItems()[0];
            $uploadsPlaylistId = $channel->getContentDetails()->getRelatedPlaylists()->getUploads();
            
            if (empty($uploadsPlaylistId)) {
                Log::warning('No uploads playlist found for authenticated user');
                return [];
            }
            
            Log::info("Found uploads playlist ID: {$uploadsPlaylistId}");
            
            // Get videos from the uploads playlist
            $playlistItemsResponse = $this->youtube->playlistItems->listPlaylistItems('snippet,contentDetails', [
                'maxResults' => $maxResults,
                'playlistId' => $uploadsPlaylistId,
            ]);
            
            $videos = [];
            
            foreach ($playlistItemsResponse->getItems() as $item) {
                $snippet = $item->getSnippet();
                $contentDetails = $item->getContentDetails();
                
                // Skip items that don't have a video ID
                if (!$contentDetails || !$contentDetails->getVideoId()) {
                    continue;
                }
                
                $videoId = $contentDetails->getVideoId();
                $title = $snippet->getTitle();
                $description = $snippet->getDescription();
                $thumbnails = $snippet->getThumbnails();
                $publishedAt = $snippet->getPublishedAt();
                
                $videos[] = [
                    'id' => $videoId,
                    'title' => $title,
                    'description' => $description,
                    'thumbnail' => $thumbnails->getHigh()->getUrl(),
                    'publishedAt' => $publishedAt,
                ];
            }
            
            Log::info("Retrieved {$playlistItemsResponse->getPageInfo()->getTotalResults()} videos from YouTube");
            
            return $videos;
            
        } catch (Exception $e) {
            Log::error('Failed to list uploaded videos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get video statistics for a list of video IDs
     *
     * @param array $videoIds Array of YouTube video IDs
     * @return array Array of video statistics indexed by video ID
     */
    public function getVideoStatistics(array $videoIds)
    {
        if (empty($videoIds)) {
            return [];
        }

        try {
            // Replace getYouTubeClient with the existing youtube instance
            $youtube = $this->youtube;
            
            // YouTube API allows a maximum of 50 videos per request
            $chunks = array_chunk($videoIds, 50);
            $statistics = [];
            
            foreach ($chunks as $chunk) {
                $response = $youtube->videos->listVideos(
                    'statistics', 
                    ['id' => implode(',', $chunk)]
                );
                
                foreach ($response->items as $item) {
                    $statistics[$item->id] = [
                        'viewCount' => $item->statistics->viewCount ?? 0,
                        'likeCount' => $item->statistics->likeCount ?? 0,
                        'commentCount' => $item->statistics->commentCount ?? 0
                    ];
                }
            }
            
            return $statistics;
        } catch (\Exception $e) {
            \Log::error('YouTube API error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Set the current YouTube account
     *
     * @param YouTubeAccount $account
     * @return bool
     */
    public function setAccount(YouTubeAccount $account): bool
    {
        try {
            // Check if the account has access tokens
            if (empty($account->access_token)) {
                Log::error('Account has no access token', ['account_id' => $account->id]);
                return false;
            }
            
            Log::info('Setting YouTube account', [
                'account_id' => $account->id,
                'name' => $account->name,
                'has_token' => !empty($account->access_token),
                'is_active' => $account->is_active,
            ]);
            
            // Ensure client is initialized
            if (!$this->client) {
                $this->client = $this->getClient();
            }
            
            // Set the access token on the client
            $accessToken = [
                'access_token' => $account->access_token,
                'refresh_token' => $account->refresh_token,
                'expires_in' => $account->token_expires_at ? $account->token_expires_at->diffInSeconds(now()) : 0,
            ];
            
            $this->client->setAccessToken($accessToken);
            
            // If token is expired and we have a refresh token, refresh it
            if ($this->client->isAccessTokenExpired() && !empty($account->refresh_token)) {
                Log::info('Refreshing expired token for account', ['account_id' => $account->id]);
                $this->client->fetchAccessTokenWithRefreshToken($account->refresh_token);
                
                // Update the account with the new token
                $newAccessToken = $this->client->getAccessToken();
                $account->access_token = $newAccessToken['access_token'];
                $account->token_expires_at = now()->addSeconds($newAccessToken['expires_in']);
                $account->save();
            }
            
            // Initialize YouTube service with the current client
            $this->youtube = new Google_Service_YouTube($this->client);
            
            // Mark this account as active
            Log::info('Marking account as active', ['account_id' => $account->id]);
            $account->markAsActive();
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to set YouTube account', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get the currently active account
     *
     * @return \App\Models\YouTubeAccount|null
     */
    public function getActiveAccount(): ?\App\Models\YouTubeAccount
    {
        return \App\Models\YouTubeAccount::getActive();
    }

    /**
     * Handle the authorization callback and save as a new account
     *
     * @param string $code The authorization code from callback
     * @param string $accountName Optional name for the account
     * @return \App\Models\YouTubeAccount|null
     */
    public function handleAuthCallbackAndSaveAccount(string $code, ?string $accountName = null): ?\App\Models\YouTubeAccount
    {
        try {
            // Get the access token
            $accessToken = $this->fetchAccessTokenWithAuthCode($code);
            
            if (!$accessToken) {
                return null;
            }
            
            // Set up the YouTube service with this token
            $this->client->setAccessToken($accessToken);
            $this->youtube = new Google_Service_YouTube($this->client);
            
            // Get channel info
            $channelInfo = $this->getChannelInfo();
            
            // Create or update account
            $account = new \App\Models\YouTubeAccount();
            $account->name = $accountName ?: ($channelInfo['title'] ?? 'YouTube Account');
            $account->email = $channelInfo['email'] ?? null;
            $account->channel_id = $channelInfo['id'] ?? null;
            $account->channel_name = $channelInfo['title'] ?? null;
            $account->access_token = $accessToken['access_token'];
            $account->refresh_token = $accessToken['refresh_token'] ?? null;
            $account->token_expires_at = now()->addSeconds($accessToken['expires_in'] ?? 3600);
            $account->account_info = $channelInfo;
            $account->save();
            
            // Mark this account as active
            $account->markAsActive();
            
            return $account;
        } catch (\Exception $e) {
            Log::error('Failed to create YouTube account from auth callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get channel information for the authenticated user
     * 
     * @return array
     */
    public function getChannelInfo(): array
    {
        try {
            $channelResponse = $this->youtube->channels->listChannels('snippet,contentDetails,statistics', [
                'mine' => true
            ]);
            
            if (empty($channelResponse->getItems())) {
                return [];
            }
            
            $channel = $channelResponse->getItems()[0];
            $snippet = $channel->getSnippet();
            $statistics = $channel->getStatistics();
            
            return [
                'id' => $channel->getId(),
                'title' => $snippet->getTitle(),
                'description' => $snippet->getDescription(),
                'thumbnails' => $snippet->getThumbnails(),
                'publishedAt' => $snippet->getPublishedAt(),
                'country' => $snippet->getCountry(),
                'viewCount' => $statistics ? $statistics->getViewCount() : 0,
                'subscriberCount' => $statistics ? $statistics->getSubscriberCount() : 0,
                'videoCount' => $statistics ? $statistics->getVideoCount() : 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get channel info', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
} 