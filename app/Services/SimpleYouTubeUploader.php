<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Process\Process;
use Exception;

final class SimpleYouTubeUploader
{
    /**
     * Upload a video to YouTube using direct username/password authentication
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
        Log::info('Using direct YouTube uploader with browser automation', [
            'video' => $videoPath,
            'title' => $title,
            'privacy' => $privacyStatus
        ]);
        
        // Check if the video file exists
        if (!file_exists($videoPath)) {
            Log::error("Video file not found: {$videoPath}");
            throw new Exception("Video file not found: {$videoPath}");
        }
        
        // Get YouTube credentials from config
        $email = Config::get('youtube.email');
        $password = Config::get('youtube.password');
        
        // Validate credentials
        if (empty($email) || empty($password)) {
            Log::error('YouTube email or password not configured');
            throw new Exception('YouTube credentials not configured. Please set YOUTUBE_EMAIL and YOUTUBE_PASSWORD in your .env file.');
        }
        
        // Convert tags array to comma-separated string
        $tagsString = implode(',', $tags);
        
        // Get the path to the upload script
        $scriptPath = '/usr/local/bin/youtube-direct-upload';
        if (!file_exists($scriptPath)) {
            // Fallback to storage directory
            $scriptPath = storage_path('app/scripts/youtube-direct-upload');
            if (!file_exists($scriptPath)) {
                Log::error("YouTube upload script not found at: {$scriptPath}");
                throw new Exception("YouTube upload script not found. Please run the installation command.");
            }
        }
        
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
            '--headless',
            $videoPath
        ];
        
        // Execute the command with longer timeout
        $process = new Process($command);
        $process->setTimeout(3600); // 1 hour timeout for large uploads
        $process->setIdleTimeout(600); // 10 minutes idle timeout
        
        // Set up process callback to stream logs in real-time
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                Log::warning("YouTube upload error output: {$buffer}");
            } else {
                Log::info("YouTube upload output: {$buffer}");
            }
        });
        
        // Get output and error
        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();
        
        // Log the process output
        Log::info('YouTube upload completed', [
            'exit_code' => $process->getExitCode(),
            'output' => $output,
            'error' => $errorOutput
        ]);
        
        // Check if upload was successful
        if (!$process->isSuccessful()) {
            Log::error('YouTube upload failed', [
                'exit_code' => $process->getExitCode(),
                'error' => $errorOutput
            ]);
            
            throw new Exception("YouTube upload failed: " . ($errorOutput ?: 'Unknown error'));
        }
        
        // Extract video ID if available
        $videoId = null;
        if (preg_match('/Video ID: ([A-Za-z0-9_-]+)/', $output, $matches)) {
            $videoId = $matches[1];
            Log::info("Successfully extracted YouTube video ID: {$videoId}");
            return $videoId;
        }
        
        // Check if upload successful but ID couldn't be extracted
        if (strpos($output, 'Upload completed successfully') !== false || 
            strpos($output, 'UPLOAD_COMPLETED_BUT_ID_UNKNOWN') !== false) {
            Log::warning('Upload completed but could not extract video ID');
            return 'UPLOAD_COMPLETED_BUT_ID_UNKNOWN';
        }
        
        Log::warning('Upload process finished but no success confirmation found');
        return null;
    }
    
    /**
     * Add a video to a YouTube playlist - Not implemented for direct upload
     *
     * @param string $videoId YouTube video ID
     * @param string $playlistId Playlist ID
     * @return string|null Playlist item ID if successful, null if failed
     */
    public function addToPlaylist(string $videoId, string $playlistId): ?string
    {
        Log::warning('Playlist functionality is not supported in the direct uploader');
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
} 