<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SimpleYouTubeUploader
{
    private string $email;
    private string $password;
    private ?string $sessionToken = null;
    private int $retryLimit = 3;
    
    public function __construct(string $email = null, string $password = null)
    {
        $this->email = $email ?? config('youtube.email');
        $this->password = $password ?? config('youtube.password');
    }
    
    /**
     * Upload a video to YouTube
     */
    public function upload(string $videoPath, string $title, string $description, array $tags = [], string $privacyStatus = 'unlisted'): ?string
    {
        Log::info("Starting YouTube direct upload for video: {$title}");
        
        try {
            if (!file_exists($videoPath)) {
                throw new Exception("Video file not found at: {$videoPath}");
            }
            
            // Store YouTube authentication credentials in .env file
            $this->updateOrCreateYouTubeCredentials();
            
            // Create the command to run youtube-upload CLI
            $tagsString = '';
            if (!empty($tags)) {
                $tagsString = '--tags="' . implode(',', $tags) . '"';
            }
            
            $privacyArg = '--privacy=' . $privacyStatus;
            
            // Construct the command
            $command = sprintf(
                'python3 /usr/local/bin/youtube-upload ' .
                '--email=%s ' .
                '--password=%s ' .
                '--title="%s" ' .
                '--description="%s" ' .
                '%s ' .  // Tags
                '%s ' .  // Privacy
                '--category="Music" ' .
                '"%s"',
                escapeshellarg($this->email),
                escapeshellarg($this->password),
                escapeshellarg($title),
                escapeshellarg($description),
                $tagsString,
                $privacyArg,
                $videoPath
            );
            
            Log::debug("Executing YouTube upload command (sensitive info redacted)");
            
            // Execute the command
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);
            
            $outputStr = implode("\n", $output);
            
            // Check if the upload was successful
            if ($returnCode !== 0) {
                Log::error("YouTube upload failed with return code {$returnCode}: {$outputStr}");
                throw new Exception("YouTube upload failed: {$outputStr}");
            }
            
            // Extract the video ID from the output
            $videoId = $this->extractVideoId($outputStr);
            
            if (!$videoId) {
                Log::error("Failed to extract video ID from command output");
                throw new Exception("Failed to extract video ID from upload response");
            }
            
            Log::info("YouTube upload successful. Video ID: {$videoId}");
            return $videoId;
            
        } catch (Exception $e) {
            Log::error("YouTube upload exception: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Add a video to a playlist
     */
    public function addToPlaylist(string $videoId, string $playlistName): ?string
    {
        Log::info("Adding video {$videoId} to playlist: {$playlistName}");
        
        try {
            // First we need to find or create the playlist
            $playlistId = $this->findOrCreatePlaylist($playlistName);
            
            if (!$playlistId) {
                throw new Exception("Failed to find or create playlist: {$playlistName}");
            }
            
            // Create the command to add video to playlist
            $command = sprintf(
                'python3 /usr/local/bin/youtube-playlist ' .
                '--email=%s ' .
                '--password=%s ' .
                '--playlist-id=%s ' .
                '--video-id=%s',
                escapeshellarg($this->email),
                escapeshellarg($this->password),
                escapeshellarg($playlistId),
                escapeshellarg($videoId)
            );
            
            // Execute the command
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);
            
            $outputStr = implode("\n", $output);
            
            // Check if the operation was successful
            if ($returnCode !== 0) {
                Log::error("Add to playlist failed with return code {$returnCode}: {$outputStr}");
                throw new Exception("Failed to add video to playlist: {$outputStr}");
            }
            
            Log::info("Video {$videoId} successfully added to playlist {$playlistId}");
            return $playlistId;
            
        } catch (Exception $e) {
            Log::error("Add to playlist exception: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Find a playlist by name or create if it doesn't exist
     */
    private function findOrCreatePlaylist(string $playlistName): ?string
    {
        Log::info("Finding or creating playlist: {$playlistName}");
        
        try {
            // Create the command to find playlists
            $findCommand = sprintf(
                'python3 /usr/local/bin/youtube-playlist-finder ' .
                '--email=%s ' .
                '--password=%s ' .
                '--name=%s',
                escapeshellarg($this->email),
                escapeshellarg($this->password),
                escapeshellarg($playlistName)
            );
            
            // Execute the command
            $output = [];
            $returnCode = 0;
            exec($findCommand . ' 2>&1', $output, $returnCode);
            
            $outputStr = implode("\n", $output);
            
            // If we found the playlist, extract its ID
            if ($returnCode === 0 && preg_match('/playlist_id=([A-Za-z0-9_-]+)/', $outputStr, $matches)) {
                $playlistId = $matches[1];
                Log::info("Found existing playlist: {$playlistId}");
                return $playlistId;
            }
            
            // If not found, create the playlist
            $createCommand = sprintf(
                'python3 /usr/local/bin/youtube-playlist-create ' .
                '--email=%s ' .
                '--password=%s ' .
                '--name=%s ' .
                '--privacy=unlisted',
                escapeshellarg($this->email),
                escapeshellarg($this->password),
                escapeshellarg($playlistName)
            );
            
            // Execute the command
            $output = [];
            $returnCode = 0;
            exec($createCommand . ' 2>&1', $output, $returnCode);
            
            $outputStr = implode("\n", $output);
            
            // Check if the operation was successful and extract the playlist ID
            if ($returnCode === 0 && preg_match('/playlist_id=([A-Za-z0-9_-]+)/', $outputStr, $matches)) {
                $playlistId = $matches[1];
                Log::info("Created new playlist: {$playlistId}");
                return $playlistId;
            }
            
            Log::error("Failed to find or create playlist: {$outputStr}");
            return null;
            
        } catch (Exception $e) {
            Log::error("Find or create playlist exception: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Extract YouTube video ID from upload command output
     */
    private function extractVideoId(string $output): ?string
    {
        // Try to find video ID in the output
        if (preg_match('/Video ID: ([A-Za-z0-9_-]+)/', $output, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/watch\?v=([A-Za-z0-9_-]+)/', $output, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Update or create YouTube credentials in .env
     */
    private function updateOrCreateYouTubeCredentials(): void
    {
        if (empty($this->email) || empty($this->password)) {
            throw new Exception('YouTube email or password not provided');
        }
        
        // Check if credentials already exist in .env
        if (config('youtube.email') && config('youtube.password')) {
            return;
        }
        
        // Update .env file with credentials
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);
        
        // Add or update email
        if (strpos($envContent, 'YOUTUBE_EMAIL=') !== false) {
            $envContent = preg_replace('/YOUTUBE_EMAIL=.*/', 'YOUTUBE_EMAIL=' . $this->email, $envContent);
        } else {
            $envContent .= "\nYOUTUBE_EMAIL=" . $this->email;
        }
        
        // Add or update password
        if (strpos($envContent, 'YOUTUBE_PASSWORD=') !== false) {
            $envContent = preg_replace('/YOUTUBE_PASSWORD=.*/', 'YOUTUBE_PASSWORD=' . $this->password, $envContent);
        } else {
            $envContent .= "\nYOUTUBE_PASSWORD=" . $this->password;
        }
        
        file_put_contents($envFile, $envContent);
    }
} 