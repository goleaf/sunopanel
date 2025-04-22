<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Process\Process;
use Exception;

final class SimpleYouTubeUploader
{
    /**
     * Upload a video to YouTube using command-line script
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
        // Generate client secrets for the upload
        $this->generateClientSecrets();
        
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
            return null;
        }
        
        // Convert tags array to comma-separated string
        $tagsString = implode(',', $tags);
        
        // Prepare the upload command
        $command = [
            '/usr/local/bin/youtube-direct-upload',
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
     * @param string $playlistTitle Playlist title
     * @return string|null Playlist ID if successful, null if failed
     */
    public function addToPlaylist(string $videoId, string $playlistTitle): ?string
    {
        // Not implemented in direct upload script yet
        // This would need additional implementation to work with playlists
        Log::warning('Adding to playlist not supported in direct upload mode');
        return null;
    }
    
    /**
     * Generate client secrets JSON file from environment variables
     *
     * @return string Path to the generated file
     */
    private function generateClientSecrets(): string
    {
        $process = new Process(['/usr/local/bin/youtube-client-secrets']);
        $process->run();
        
        if (!$process->isSuccessful()) {
            throw new Exception('Failed to generate client secrets: ' . $process->getErrorOutput());
        }
        
        return '/tmp/client_secrets.json';
    }
} 