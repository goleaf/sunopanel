<?php

namespace App\Services;

use App\Exceptions\YouTubeException;
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
            Log::info('Fetching access token with auth code', [
                'code_length' => strlen($code),
                'client_configured' => !empty($this->client->getClientId()),
            ]);

            // Ensure client is properly configured
            if (!$this->client->getClientId() || !$this->client->getClientSecret()) {
                throw new \Exception('OAuth client not properly configured');
            }

            // Fetch the access token
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
            
            // Check for errors in the response
            if (isset($accessToken['error'])) {
                $errorMsg = $accessToken['error_description'] ?? $accessToken['error'];
                Log::error('OAuth token exchange failed', [
                    'error' => $accessToken['error'],
                    'error_description' => $accessToken['error_description'] ?? null,
                ]);
                throw new \Exception("OAuth error: {$errorMsg}");
            }

            // Validate required token fields
            if (!isset($accessToken['access_token'])) {
                Log::error('Access token missing from OAuth response', $accessToken);
                throw new \Exception('Access token not received from OAuth provider');
            }

            // Log successful token exchange (without sensitive data)
            Log::info('Access token fetched successfully', [
                'has_access_token' => !empty($accessToken['access_token']),
                'has_refresh_token' => !empty($accessToken['refresh_token']),
                'expires_in' => $accessToken['expires_in'] ?? null,
                'token_type' => $accessToken['token_type'] ?? null,
                'scope' => $accessToken['scope'] ?? null,
            ]);

            // Set the token on the client
            $this->client->setAccessToken($accessToken);
            
            // Save the token to database
            $this->saveAccessToken($accessToken);
            
            // Initialize YouTube service with new token
            $this->youtube = new Google_Service_YouTube($this->client);
            
            return $accessToken;
            
        } catch (\Google_Service_Exception $e) {
            Log::error('Google Service error during token exchange', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'errors' => $e->getErrors(),
            ]);
            throw new \Exception("Google API error: {$e->getMessage()}");
            
        } catch (\Exception $e) {
            Log::error('Error fetching YouTube access token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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
        
        // For OAuth, ensure we have a valid token
        return $this->ensureValidToken();
    }

    /**
     * Check if the YouTube account is in good standing and can upload videos
     *
     * @return array Status information with 'can_upload' boolean and 'reason' if not
     */
    public function checkAccountStatus(): array
    {
        if (!$this->isAuthenticated()) {
            return [
                'can_upload' => false,
                'reason' => 'Not authenticated',
                'error_type' => 'authentication'
            ];
        }

        try {
            // Try to get channel info to check if account is suspended
            $channels = $this->youtube->channels->listChannels('snippet,status', [
                'mine' => true,
            ]);

            if (empty($channels->getItems())) {
                return [
                    'can_upload' => false,
                    'reason' => 'No YouTube channel found for this account',
                    'error_type' => 'no_channel'
                ];
            }

            $channel = $channels->getItems()[0];
            $status = $channel->getStatus();

            if ($status && !$status->getIsLinked()) {
                return [
                    'can_upload' => false,
                    'reason' => 'YouTube channel is not linked properly',
                    'error_type' => 'not_linked'
                ];
            }

            return [
                'can_upload' => true,
                'reason' => 'Account is in good standing',
                'channel_title' => $channel->getSnippet()->getTitle(),
                'channel_id' => $channel->getId()
            ];

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            if (str_contains($errorMessage, 'suspended') || str_contains($errorMessage, 'authenticatedUserAccountSuspended')) {
                return [
                    'can_upload' => false,
                    'reason' => 'YouTube account is suspended',
                    'error_type' => 'account_suspended',
                    'original_error' => $errorMessage
                ];
            } elseif (str_contains($errorMessage, '403') || str_contains($errorMessage, 'forbidden')) {
                return [
                    'can_upload' => false,
                    'reason' => 'Access forbidden - insufficient permissions',
                    'error_type' => 'permission_denied',
                    'original_error' => $errorMessage
                ];
            } else {
                return [
                    'can_upload' => false,
                    'reason' => 'Unknown error checking account status: ' . $errorMessage,
                    'error_type' => 'unknown',
                    'original_error' => $errorMessage
                ];
            }
        }
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
            throw YouTubeException::authentication('YouTube API not authenticated. Please authenticate first.');
        }

        // Check account status before attempting upload
        $accountStatus = $this->checkAccountStatus();
        if (!$accountStatus['can_upload']) {
            Log::error('YouTube account cannot upload videos', $accountStatus);
            
            switch ($accountStatus['error_type']) {
                case 'account_suspended':
                    throw YouTubeException::permissionDenied($accountStatus['reason'], $accountStatus);
                case 'permission_denied':
                    throw YouTubeException::permissionDenied($accountStatus['reason'], $accountStatus);
                case 'no_channel':
                    throw YouTubeException::authentication($accountStatus['reason'], $accountStatus);
                default:
                    throw YouTubeException::upload($accountStatus['reason'], $accountStatus);
            }
        }

        Log::info('YouTube account status check passed', [
            'channel_title' => $accountStatus['channel_title'] ?? 'Unknown',
            'channel_id' => $accountStatus['channel_id'] ?? 'Unknown'
        ]);
        
        if (!file_exists($videoPath)) {
            Log::error("Video file not found: {$videoPath}");
            throw YouTubeException::fileNotFound($videoPath);
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
                throw YouTubeException::upload("Failed to open the file for reading: {$videoPath}", [
                    'file_path' => $videoPath,
                    'file_size' => $fileSize
                ]);
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
                    
                    // Determine error type based on exception message
                    $errorMessage = $e->getMessage();
                    
                    // Check for specific YouTube API errors
                    if (str_contains($errorMessage, 'suspended') || str_contains($errorMessage, 'authenticatedUserAccountSuspended')) {
                        throw YouTubeException::permissionDenied("YouTube account is suspended. Please check your account status and try again with a different account.", [
                            'chunk_number' => $chunkNumber,
                            'uploaded_bytes' => $uploadedBytes,
                            'total_bytes' => $fileSize,
                            'original_error' => $errorMessage,
                            'error_type' => 'account_suspended'
                        ]);
                    } elseif (str_contains($errorMessage, 'quota') || str_contains($errorMessage, 'limit')) {
                        throw YouTubeException::apiQuota("Upload quota exceeded during chunk upload", [
                            'chunk_number' => $chunkNumber,
                            'uploaded_bytes' => $uploadedBytes,
                            'total_bytes' => $fileSize,
                            'original_error' => $errorMessage
                        ]);
                    } elseif (str_contains($errorMessage, 'network') || str_contains($errorMessage, 'timeout')) {
                        throw YouTubeException::network("Network error during chunk upload", [
                            'chunk_number' => $chunkNumber,
                            'uploaded_bytes' => $uploadedBytes,
                            'original_error' => $errorMessage
                        ]);
                    } elseif (str_contains($errorMessage, '403') || str_contains($errorMessage, 'forbidden')) {
                        throw YouTubeException::permissionDenied("Access forbidden. Please check your YouTube account permissions.", [
                            'chunk_number' => $chunkNumber,
                            'uploaded_bytes' => $uploadedBytes,
                            'total_bytes' => $fileSize,
                            'original_error' => $errorMessage
                        ]);
                    } else {
                        throw YouTubeException::upload("Error during chunk upload", [
                            'chunk_number' => $chunkNumber,
                            'uploaded_bytes' => $uploadedBytes,
                            'original_error' => $errorMessage
                        ]);
                    }
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
            
            // Validate and refresh token if needed
            if (!$this->ensureValidToken($account)) {
                Log::error('Failed to ensure valid token for account', ['account_id' => $account->id]);
                return false;
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

    /**
     * Check if the access token is expired.
     */
    public function isTokenExpired(): bool
    {
        return $this->client->isAccessTokenExpired();
    }

    /**
     * Refresh the access token using the refresh token
     *
     * @param YouTubeAccount|null $account
     * @return bool
     */
    public function refreshAccessToken(?YouTubeAccount $account = null): bool
    {
        try {
            $account = $account ?: $this->getActiveAccount();
            
            if (!$account || !$account->refresh_token) {
                Log::warning('No account or refresh token available for token refresh', [
                    'has_account' => !is_null($account),
                    'has_refresh_token' => $account ? !empty($account->refresh_token) : false,
                ]);
                return false;
            }

            Log::info('Refreshing access token for account', [
                'account_id' => $account->id,
                'channel_title' => $account->channel_title,
            ]);

            // Use the refresh token to get a new access token
            $this->client->fetchAccessTokenWithRefreshToken($account->refresh_token);
            $newAccessToken = $this->client->getAccessToken();

            // Check for errors in the response
            if (!$newAccessToken || isset($newAccessToken['error'])) {
                $error = $newAccessToken['error'] ?? 'Unknown error';
                $errorDescription = $newAccessToken['error_description'] ?? '';
                
                Log::error('Failed to refresh access token', [
                    'account_id' => $account->id,
                    'error' => $error,
                    'error_description' => $errorDescription,
                ]);
                
                // If refresh token is invalid, mark account as needing re-authentication
                if (in_array($error, ['invalid_grant', 'invalid_request'])) {
                    $account->update([
                        'access_token' => null,
                        'refresh_token' => null,
                        'token_expires_at' => null,
                        'is_active' => false,
                    ]);
                    Log::warning('Refresh token invalid, account marked for re-authentication', [
                        'account_id' => $account->id,
                    ]);
                }
                
                return false;
            }

            // Validate the new access token
            if (!isset($newAccessToken['access_token'])) {
                Log::error('New access token missing from refresh response', [
                    'account_id' => $account->id,
                    'response_keys' => array_keys($newAccessToken),
                ]);
                return false;
            }

            // Update the account with the new token
            $updateData = [
                'access_token' => $newAccessToken['access_token'],
                'token_expires_at' => now()->addSeconds($newAccessToken['expires_in'] ?? 3600),
                'last_used_at' => now(),
            ];
            
            // Update refresh token if a new one was provided
            if (isset($newAccessToken['refresh_token'])) {
                $updateData['refresh_token'] = $newAccessToken['refresh_token'];
                Log::info('New refresh token received and saved', ['account_id' => $account->id]);
            }
            
            $account->update($updateData);

            // Update the legacy credential system for backward compatibility
            if ($this->credential) {
                $this->credential->update([
                    'access_token' => $newAccessToken['access_token'],
                    'token_created_at' => time(),
                    'token_expires_in' => $newAccessToken['expires_in'] ?? 3600,
                    'refresh_token' => $newAccessToken['refresh_token'] ?? $this->credential->refresh_token,
                ]);
            }

            Log::info('Access token refreshed successfully', [
                'account_id' => $account->id,
                'expires_in' => $newAccessToken['expires_in'] ?? 3600,
            ]);
            
            return true;

        } catch (\Google_Service_Exception $e) {
            Log::error('Google Service error during token refresh', [
                'account_id' => $account?->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'errors' => $e->getErrors(),
            ]);
            return false;
            
        } catch (\Exception $e) {
            Log::error('Failed to refresh access token', [
                'account_id' => $account?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Validate and refresh token if needed
     *
     * @param YouTubeAccount|null $account
     * @return bool
     */
    public function ensureValidToken(?YouTubeAccount $account = null): bool
    {
        try {
            $account = $account ?: $this->getActiveAccount();
            
            if (!$account) {
                Log::warning('No active YouTube account found for token validation');
                return false;
            }

            // Check if we have an access token
            if (!$account->access_token) {
                Log::warning('Account has no access token', [
                    'account_id' => $account->id,
                    'channel_title' => $account->channel_title,
                ]);
                return false;
            }

            // Set the token on the client
            $accessToken = [
                'access_token' => $account->access_token,
                'refresh_token' => $account->refresh_token,
                'expires_in' => $account->token_expires_at ? max(0, $account->token_expires_at->diffInSeconds(now())) : 0,
            ];
            
            $this->client->setAccessToken($accessToken);

            // If token is expired, try to refresh it
            if ($this->client->isAccessTokenExpired()) {
                Log::info('Token expired, attempting refresh', [
                    'account_id' => $account->id,
                    'expires_at' => $account->token_expires_at?->toISOString(),
                ]);
                
                if (!$account->refresh_token) {
                    Log::error('Token expired but no refresh token available', [
                        'account_id' => $account->id,
                    ]);
                    return false;
                }
                
                return $this->refreshAccessToken($account);
            }

            // Update last used timestamp
            $account->touch('last_used_at');

            Log::debug('Token validation successful', [
                'account_id' => $account->id,
                'expires_at' => $account->token_expires_at?->toISOString(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to ensure valid token', [
                'account_id' => $account?->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Revoke the access token for an account
     *
     * @param YouTubeAccount $account
     * @return bool
     */
    public function revokeToken(YouTubeAccount $account): bool
    {
        try {
            if (!$account->access_token) {
                Log::info('Account already has no access token', ['account_id' => $account->id]);
                return true; // Already revoked
            }

            Log::info('Revoking access token for account', [
                'account_id' => $account->id,
                'channel_title' => $account->channel_title,
            ]);

            // Set the token on the client
            $accessToken = [
                'access_token' => $account->access_token,
                'refresh_token' => $account->refresh_token,
            ];
            
            $this->client->setAccessToken($accessToken);

            // Revoke the token with Google
            try {
                $this->client->revokeToken();
                Log::info('Token revoked with Google successfully', ['account_id' => $account->id]);
            } catch (\Exception $e) {
                Log::warning('Failed to revoke token with Google, but will clear locally', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Clear the tokens from the account
            $account->update([
                'access_token' => null,
                'refresh_token' => null,
                'token_expires_at' => null,
                'is_active' => false,
            ]);

            Log::info('Token revoked and account deactivated successfully', ['account_id' => $account->id]);
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to revoke token', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Upload multiple videos in batch
     *
     * @param array $videos Array of video data with keys: path, title, description, tags, privacyStatus, etc.
     * @param callable|null $progressCallback Callback function to report progress
     * @return array Results with success/failure status for each video
     */
    public function bulkUploadVideos(array $videos, ?callable $progressCallback = null): array
    {
        $results = [];
        $totalVideos = count($videos);
        
        Log::info("Starting bulk upload of {$totalVideos} videos");
        
        if (!$this->isAuthenticated()) {
            Log::error('YouTube API not authenticated for bulk upload');
            return array_fill(0, $totalVideos, [
                'success' => false,
                'error' => 'YouTube API not authenticated',
                'video_id' => null
            ]);
        }
        
        foreach ($videos as $index => $videoData) {
            $videoNumber = $index + 1;
            
            try {
                Log::info("Uploading video {$videoNumber}/{$totalVideos}: {$videoData['title']}");
                
                // Call progress callback if provided
                if ($progressCallback) {
                    $progressCallback($videoNumber, $totalVideos, 'uploading', $videoData['title']);
                }
                
                $videoId = $this->uploadVideo(
                    $videoData['path'],
                    $videoData['title'],
                    $videoData['description'] ?? '',
                    $videoData['tags'] ?? [],
                    $videoData['privacyStatus'] ?? 'unlisted',
                    $videoData['madeForKids'] ?? false,
                    $videoData['isShort'] ?? false,
                    $videoData['categoryId'] ?? '10'
                );
                
                if ($videoId) {
                    $results[] = [
                        'success' => true,
                        'video_id' => $videoId,
                        'title' => $videoData['title'],
                        'error' => null
                    ];
                    
                    Log::info("Successfully uploaded video {$videoNumber}/{$totalVideos}: {$videoId}");
                    
                    // Add to playlist if specified
                    if (!empty($videoData['playlistId'])) {
                        $this->addVideoToPlaylist($videoId, $videoData['playlistId']);
                    }
                    
                } else {
                    $results[] = [
                        'success' => false,
                        'video_id' => null,
                        'title' => $videoData['title'],
                        'error' => 'Upload failed - unknown error'
                    ];
                    
                    Log::error("Failed to upload video {$videoNumber}/{$totalVideos}: {$videoData['title']}");
                }
                
                // Call progress callback for completion
                if ($progressCallback) {
                    $progressCallback($videoNumber, $totalVideos, $videoId ? 'completed' : 'failed', $videoData['title']);
                }
                
                // Add a small delay between uploads to avoid rate limiting
                if ($videoNumber < $totalVideos) {
                    sleep(2);
                }
                
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'video_id' => null,
                    'title' => $videoData['title'] ?? 'Unknown',
                    'error' => $e->getMessage()
                ];
                
                Log::error("Exception during video upload {$videoNumber}/{$totalVideos}", [
                    'title' => $videoData['title'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Call progress callback for error
                if ($progressCallback) {
                    $progressCallback($videoNumber, $totalVideos, 'error', $videoData['title'] ?? 'Unknown');
                }
            }
        }
        
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        Log::info("Bulk upload completed: {$successCount}/{$totalVideos} videos uploaded successfully");
        
        return $results;
    }

    /**
     * Get upload quota information
     *
     * @return array
     */
    public function getUploadQuota(): array
    {
        try {
            // YouTube API doesn't provide direct quota information
            // This is a placeholder for quota tracking implementation
            return [
                'daily_limit' => 10000, // Default daily quota units
                'used_today' => 0, // Would need to track this
                'remaining' => 10000,
                'reset_time' => now()->addDay()->startOfDay(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get upload quota', ['error' => $e->getMessage()]);
            return [
                'daily_limit' => 0,
                'used_today' => 0,
                'remaining' => 0,
                'reset_time' => now(),
            ];
        }
    }

    /**
     * Retry failed uploads with exponential backoff
     *
     * @param array $failedUploads
     * @param int $maxRetries
     * @param callable|null $progressCallback
     * @return array
     */
    public function retryFailedUploads(array $failedUploads, int $maxRetries = 3, ?callable $progressCallback = null): array
    {
        $results = [];
        
        foreach ($failedUploads as $upload) {
            $retryCount = 0;
            $success = false;
            
            while ($retryCount < $maxRetries && !$success) {
                $retryCount++;
                $waitTime = pow(2, $retryCount); // Exponential backoff: 2, 4, 8 seconds
                
                Log::info("Retrying upload (attempt {$retryCount}/{$maxRetries}): {$upload['title']}");
                
                if ($progressCallback) {
                    $progressCallback($retryCount, $maxRetries, 'retrying', $upload['title']);
                }
                
                sleep($waitTime);
                
                try {
                    $videoId = $this->uploadVideo(
                        $upload['path'],
                        $upload['title'],
                        $upload['description'] ?? '',
                        $upload['tags'] ?? [],
                        $upload['privacyStatus'] ?? 'unlisted',
                        $upload['madeForKids'] ?? false,
                        $upload['isShort'] ?? false,
                        $upload['categoryId'] ?? '10'
                    );
                    
                    if ($videoId) {
                        $success = true;
                        $results[] = [
                            'success' => true,
                            'video_id' => $videoId,
                            'title' => $upload['title'],
                            'retry_count' => $retryCount,
                            'error' => null
                        ];
                        
                        Log::info("Retry successful for: {$upload['title']} (attempt {$retryCount})");
                    }
                    
                } catch (\Exception $e) {
                    Log::warning("Retry attempt {$retryCount} failed for: {$upload['title']}", [
                        'error' => $e->getMessage()
                    ]);
                    
                    if ($retryCount >= $maxRetries) {
                        $results[] = [
                            'success' => false,
                            'video_id' => null,
                            'title' => $upload['title'],
                            'retry_count' => $retryCount,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }
        }
        
        return $results;
    }
} 