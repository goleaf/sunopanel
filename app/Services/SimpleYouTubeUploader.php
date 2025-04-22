<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Process\Process;
use Exception;

class SimpleYouTubeUploader
{
    /**
     * @var YouTubeApiService|null
     */
    protected $youtubeApiService;

    /**
     * Create a new YouTube uploader instance.
     *
     * @param YouTubeApiService|null $youtubeApiService
     */
    public function __construct(YouTubeApiService $youtubeApiService = null)
    {
        $this->youtubeApiService = $youtubeApiService;
    }

    /**
     * Upload a video to YouTube.
     *
     * @param string $videoPath Path to the video file
     * @param string $title Video title
     * @param string $description Video description
     * @param array|string $tags Video tags (array or comma-separated string)
     * @param string $privacyStatus Privacy setting (public, unlisted, private)
     * @param string|int $category Video category (name or ID)
     * 
     * @return string|null The YouTube video ID if successful, null if failed
     * @throws Exception If an error occurs during upload
     */
    public function upload(
        string $videoPath,
        string $title,
        string $description = '',
        $tags = [],
        string $privacyStatus = 'unlisted',
        $category = 'Music'
    ): ?string {
        // Check if file exists
        if (!file_exists($videoPath)) {
            Log::error("Video file not found: {$videoPath}");
            throw new Exception("Video file not found: {$videoPath}");
        }
        
        // Convert tags to the appropriate format
        if (is_string($tags)) {
            $tags = !empty($tags) ? array_map('trim', explode(',', $tags)) : [];
        }
        
        // Determine category ID
        $categoryId = is_numeric($category) ? $category : $this->getCategoryId($category);
        
        // Determine which upload method to use
        $useOAuth = config('youtube.use_oauth', false) && $this->youtubeApiService && $this->youtubeApiService->isAuthenticated();
        
        // Attempt to use OAuth first if enabled
        if ($useOAuth) {
            try {
                Log::info("Using YouTube API OAuth for upload", [
                    'video' => $videoPath,
                    'title' => $title,
                    'privacy' => $privacyStatus,
                ]);
                
                return $this->youtubeApiService->uploadVideo(
                    $videoPath,
                    $title,
                    $description,
                    $tags,
                    $privacyStatus,
                    (string) $categoryId
                );
            } catch (Exception $e) {
                Log::error("YouTube API upload failed: " . $e->getMessage(), [
                    'video' => $videoPath,
                    'exception' => $e,
                ]);
                
                // If OAuth fails, try the simple uploader as fallback (if enabled)
                if (config('youtube.use_simple', true)) {
                    Log::info("Falling back to direct YouTube uploader");
                } else {
                    throw $e; // No fallback allowed, re-throw the exception
                }
            }
        }
        
        // Use simple uploader if OAuth is not enabled or failed
        if (config('youtube.use_simple', true)) {
            return $this->uploadWithScript(
                $videoPath,
                $title,
                $description,
                $tags,
                $privacyStatus,
                $category
            );
        }
        
        Log::error("No YouTube upload method available. Enable OAuth or simple uploader.");
        throw new Exception("No YouTube upload method available. Enable OAuth or simple uploader.");
    }
    
    /**
     * Upload using the command-line script (legacy method).
     *
     * @param string $videoPath
     * @param string $title
     * @param string $description
     * @param array $tags
     * @param string $privacyStatus
     * @param string $category
     * 
     * @return string|null
     * @throws Exception
     */
    protected function uploadWithScript(
        string $videoPath,
        string $title,
        string $description,
        array $tags,
        string $privacyStatus,
        string $category
    ): ?string {
        try {
            // Generate client secrets file
            $this->generateClientSecrets();
            
            // Get YouTube credentials from config
            $email = config('youtube.email');
            $password = config('youtube.password');
            
            if (empty($email) || empty($password)) {
                Log::error('YouTube credentials not configured');
                throw new Exception('YouTube credentials (email/password) are not configured');
            }
            
            Log::info("Using direct YouTube uploader with browser automation", [
                'video' => $videoPath,
                'title' => $title,
                'privacy' => $privacyStatus,
            ]);
            
            // Prepare the upload command
            $uploadScript = config('youtube.upload_script', '/usr/local/bin/youtube-direct-upload');
            if (!file_exists($uploadScript)) {
                Log::error("YouTube upload script not found: {$uploadScript}");
                throw new Exception("YouTube upload script not found: {$uploadScript}");
            }
            
            // Format tags as comma-separated string
            $tagsString = implode(',', $tags);
            
            // Build command array
            $command = [
                $uploadScript,
                '--email', $email,
                '--password', $password,
                '--title', $title,
                '--privacy', $privacyStatus,
            ];
            
            // Add optional parameters
            if (!empty($description)) {
                $command[] = '--description';
                $command[] = $description;
            }
            
            if (!empty($tagsString)) {
                $command[] = '--tags';
                $command[] = $tagsString;
            }
            
            if (!empty($category)) {
                $command[] = '--category';
                $command[] = $category;
            }
            
            // Add the video file path as the last argument
            $command[] = $videoPath;
            
            // Execute the command
            $process = Process::timeout(
                config('youtube.process_timeout', 3600)
            )->run($command);
            
            // Get output and error output
            $output = $process->output();
            $errorOutput = $process->errorOutput();
            
            Log::info('YouTube upload output', [
                'exit_code' => $process->exitCode(),
                'output' => $output,
                'error' => $errorOutput
            ]);
            
            // If the process failed, log the error and throw an exception
            if (!$process->successful()) {
                Log::error('YouTube upload failed', [
                    'exit_code' => $process->exitCode(),
                    'error' => $errorOutput
                ]);
                
                throw new Exception('YouTube upload failed: ' . ($errorOutput ?: 'Unknown error'));
            }
            
            // Extract the video ID from the output
            $videoId = null;
            if (preg_match('/Video ID: ([A-Za-z0-9_-]+)/', $output, $matches)) {
                $videoId = $matches[1];
                Log::info("YouTube upload successful. Video ID: {$videoId}");
                return $videoId;
            }
            
            Log::warning('YouTube upload completed but no video ID found in the output');
            return null;
            
        } catch (Exception $e) {
            Log::error('Exception during YouTube upload: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Add a video to a YouTube playlist.
     *
     * @param string $videoId The YouTube video ID
     * @param string $playlistId The YouTube playlist ID
     * @return bool True if successful, false otherwise
     */
    public function addToPlaylist(string $videoId, string $playlistId): bool
    {
        // Use OAuth method if available
        if (config('youtube.use_oauth', false) && $this->youtubeApiService && $this->youtubeApiService->isAuthenticated()) {
            try {
                return $this->youtubeApiService->addVideoToPlaylist($videoId, $playlistId);
            } catch (Exception $e) {
                Log::error('Failed to add video to playlist using API: ' . $e->getMessage());
                return false;
            }
        }
        
        // No direct browser automation option available for playlist updates
        Log::warning('Adding videos to playlists requires YouTube API OAuth authentication');
        return false;
    }
    
    /**
     * Find or create a playlist by name.
     *
     * @param string $playlistName The name of the playlist
     * @param string $description Playlist description
     * @param string $privacyStatus Privacy setting
     * @return string|null Playlist ID if successful, null otherwise
     */
    public function findOrCreatePlaylist(
        string $playlistName, 
        string $description = '', 
        string $privacyStatus = 'public'
    ): ?string {
        // Use OAuth method if available
        if (config('youtube.use_oauth', false) && $this->youtubeApiService && $this->youtubeApiService->isAuthenticated()) {
            try {
                return $this->youtubeApiService->findOrCreatePlaylist(
                    $playlistName,
                    $description,
                    $privacyStatus
                );
            } catch (Exception $e) {
                Log::error('Failed to find or create playlist using API: ' . $e->getMessage());
                return null;
            }
        }
        
        // No direct browser automation option available for playlist creation
        Log::warning('Creating playlists requires YouTube API OAuth authentication');
        return null;
    }

    /**
     * Get YouTube category ID by name.
     *
     * @param string $categoryName
     * @return int
     */
    public function getCategoryId(string $categoryName): int
    {
        $categories = [
            'film'              => 1,
            'animation'         => 1,
            'autos'             => 2,
            'vehicles'          => 2,
            'music'             => 10,
            'pets'              => 15,
            'animals'           => 15,
            'sports'            => 17,
            'short movies'      => 18,
            'travel'            => 19,
            'events'            => 19,
            'gaming'            => 20,
            'videoblogging'     => 21,
            'people'            => 22,
            'blogs'             => 22,
            'comedy'            => 23,
            'entertainment'     => 24,
            'news'              => 25,
            'politics'          => 25,
            'howto'             => 26,
            'style'             => 26,
            'education'         => 27,
            'science'           => 28,
            'technology'        => 28,
            'nonprofits'        => 29,
            'activism'          => 29,
            'movies'            => 30,
            'anime'             => 31,
            'action'            => 31,
            'adventure'         => 31,
            'classics'          => 32,
            'comedy'            => 34,
            'documentary'       => 35,
            'drama'             => 36,
            'family'            => 37,
            'foreign'           => 38,
            'horror'            => 39,
            'sci-fi'            => 40,
            'fantasy'           => 40,
            'thriller'          => 41,
            'shorts'            => 42,
            'shows'             => 43,
            'trailers'          => 44
        ];
        
        $categoryName = strtolower($categoryName);
        return $categories[$categoryName] ?? 10; // Default to Music (10) if not found
    }

    /**
     * Generate client secrets file for YouTube API.
     *
     * @return string Path to the generated file
     * @throws Exception If generation fails
     */
    protected function generateClientSecrets(): string
    {
        $clientSecrets = base_path('vendor/bin/youtube-client-secrets');
        
        if (!file_exists($clientSecrets)) {
            Log::error("YouTube client secrets generator not found: {$clientSecrets}");
            throw new Exception("YouTube client secrets generator not found: {$clientSecrets}");
        }
        
        $process = Process::run([$clientSecrets]);
        
        if (!$process->successful()) {
            Log::error('Failed to generate client secrets: ' . $process->errorOutput());
            throw new Exception('Failed to generate client secrets: ' . $process->errorOutput());
        }
        
        return '/tmp/client_secrets.json';
    }
} 