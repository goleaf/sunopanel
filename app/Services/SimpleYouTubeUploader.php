<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Process\Process;
use Exception;

final class SimpleYouTubeUploader
{
    private ?YouTubeUploader $oauthUploader = null;
    
    public function __construct(YouTubeUploader $youtubeUploader = null)
    {
        $this->oauthUploader = $youtubeUploader;
    }
    
    /**
     * Upload a video to YouTube using either OAuth or the command-line script
     *
     * @param string $videoPath Path to the video file
     * @param string $title Video title
     * @param string $description Video description
     * @param array $tags Video tags
     * @param string $privacyStatus Privacy status (public, unlisted, private)
     * @param string $category Video category
     * @return string|null YouTube video ID if successful, null if failed
     */
    public function upload(
        string $videoPath,
        string $title,
        string $description = '',
        array $tags = [],
        string $privacyStatus = 'unlisted',
        string $category = 'Music'
    ): ?string {
        // Try to use OAuth-based uploader if available and configured
        $useOAuth = Config::get('youtube.use_oauth', false) && 
                   Config::get('youtube.access_token') && 
                   Config::get('youtube.refresh_token');
        
        if ($useOAuth && $this->oauthUploader) {
            Log::info('Using OAuth-based YouTube uploader');
            
            // Map category name to ID if needed
            $categoryId = is_numeric($category) ? $category : $this->getCategoryId($category);
            
            return $this->oauthUploader->upload(
                $videoPath,
                $title,
                $description,
                $tags,
                $privacyStatus,
                $categoryId
            );
        }
        
        // Fall back to command-line script if it exists
        $scriptPath = base_path('vendor/bin/youtube-direct-upload');
        if (!file_exists($scriptPath)) {
            Log::error("YouTube upload script not found: {$scriptPath}");
            
            // If OAuth uploader is available but not configured, use it as fallback
            if ($this->oauthUploader) {
                Log::info('Falling back to OAuth uploader without tokens');
                $authUrl = $this->oauthUploader->getAuthUrl();
                Log::info("Authentication URL: {$authUrl}");
                throw new Exception('YouTube upload script not found. Please authenticate using OAuth first at: ' . $authUrl);
            }
            
            throw new Exception('YouTube upload script not found and OAuth not available. Please set up YouTube authentication.');
        }
        
        Log::info('Using command-line YouTube uploader');
        
        // Try to generate client secrets for the upload
        try {
            $this->generateClientSecrets();
        } catch (Exception $e) {
            Log::error('Failed to generate client secrets: ' . $e->getMessage());
            
            // If OAuth uploader is available, use it as fallback
            if ($this->oauthUploader) {
                Log::info('Falling back to OAuth uploader due to client secrets failure');
                $authUrl = $this->oauthUploader->getAuthUrl();
                Log::info("Authentication URL: {$authUrl}");
                throw new Exception('Failed to generate client secrets. Please authenticate using OAuth first at: ' . $authUrl);
            }
            
            throw $e;
        }
        
        // Check if the video file exists
        if (!file_exists($videoPath)) {
            Log::error("Video file not found: {$videoPath}");
            return null;
        }
        
        // Get YouTube account credentials from config
        $email = Config::get('youtube.email');
        $password = Config::get('youtube.password');
        
        if (empty($email) || empty($password)) {
            Log::error('YouTube email or password not configured');
            
            // If OAuth uploader is available, use it as fallback
            if ($this->oauthUploader) {
                Log::info('Falling back to OAuth uploader due to missing credentials');
                $authUrl = $this->oauthUploader->getAuthUrl();
                Log::info("Authentication URL: {$authUrl}");
                throw new Exception('YouTube email or password not configured. Please authenticate using OAuth first at: ' . $authUrl);
            }
            
            return null;
        }
        
        // Convert tags array to comma-separated string
        $tagsString = implode(',', $tags);
        
        // Prepare the upload command
        $command = [
            $scriptPath,
            '--email', $email,
            '--password', $password,
            '--title', $title,
            '--description', $description,
            '--tags', $tagsString,
            '--privacy', $privacyStatus,
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
                'exit_code' => $process->getExitCode(),
                'error' => $errorOutput
            ]);
            
            return null;
        }
        
        // Extract video ID if available
        $videoId = null;
        if (preg_match('/Video ID: ([A-Za-z0-9_-]+)/', $output, $matches)) {
            $videoId = $matches[1];
            Log::info("Successfully extracted YouTube video ID: {$videoId}");
            return $videoId;
        }
        
        Log::warning('Upload completed but could not extract video ID');
        return null;
    }
    
    /**
     * Add a video to a YouTube playlist
     *
     * @param string $videoId YouTube video ID
     * @param string $playlistId Playlist ID
     * @return string|null Playlist item ID if successful, null if failed
     */
    public function addToPlaylist(string $videoId, string $playlistId): ?string
    {
        // Try to use OAuth-based uploader if available and configured
        $useOAuth = Config::get('youtube.use_oauth', false) && 
                   Config::get('youtube.access_token') && 
                   Config::get('youtube.refresh_token');
        
        if ($useOAuth && $this->oauthUploader) {
            Log::info('Using OAuth-based YouTube uploader for playlist');
            return $this->oauthUploader->addToPlaylist($videoId, $playlistId);
        }
        
        // Not implemented in direct upload script
        Log::warning('Adding to playlist not supported in direct upload mode');
        
        // If OAuth uploader is available but not configured, suggest it
        if ($this->oauthUploader) {
            $authUrl = $this->oauthUploader->getAuthUrl();
            Log::info("Authentication URL for playlist support: {$authUrl}");
            throw new Exception('Playlist support requires OAuth authentication. Please authenticate at: ' . $authUrl);
        }
        
        return null;
    }
    
    /**
     * Map category name to YouTube category ID
     *
     * @param string $categoryName
     * @return string Category ID
     */
    private function getCategoryId(string $categoryName): string
    {
        $categories = [
            'Film & Animation' => '1',
            'Autos & Vehicles' => '2',
            'Music' => '10',
            'Pets & Animals' => '15',
            'Sports' => '17',
            'Short Movies' => '18',
            'Travel & Events' => '19',
            'Gaming' => '20',
            'Videoblogging' => '21',
            'People & Blogs' => '22',
            'Comedy' => '23',
            'Entertainment' => '24',
            'News & Politics' => '25',
            'Howto & Style' => '26',
            'Education' => '27',
            'Science & Technology' => '28',
            'Nonprofits & Activism' => '29',
            'Movies' => '30',
            'Anime/Animation' => '31',
            'Action/Adventure' => '32',
            'Classics' => '33',
            'Documentary' => '35',
            'Drama' => '36',
            'Family' => '37',
            'Foreign' => '38',
            'Horror' => '39',
            'Sci-Fi/Fantasy' => '40',
            'Thriller' => '41',
            'Shorts' => '42',
            'Shows' => '43',
            'Trailers' => '44',
        ];
        
        return $categories[$categoryName] ?? '10'; // Default to Music (10)
    }
    
    /**
     * Generate client secrets JSON file from environment variables
     *
     * @return string Path to the generated file
     * @throws Exception If client secrets script is not found
     */
    private function generateClientSecrets(): string
    {
        $scriptPath = base_path('vendor/bin/youtube-client-secrets');
        
        if (!file_exists($scriptPath)) {
            throw new Exception("Client secrets generation script not found: {$scriptPath}");
        }
        
        $process = new Process([$scriptPath]);
        $process->run();
        
        if (!$process->isSuccessful()) {
            throw new Exception('Failed to generate client secrets: ' . $process->getErrorOutput());
        }
        
        return '/tmp/client_secrets.json';
    }
} 