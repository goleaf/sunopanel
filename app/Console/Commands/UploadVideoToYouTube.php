<?php

namespace App\Console\Commands;

use App\Services\SimpleYouTubeUploader;
use App\Services\YouTubeCredentialsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class UploadVideoToYouTube extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:upload 
                            {file : Path to MP4 file} 
                            {title? : Video title} 
                            {description? : Video description} 
                            {tags? : Comma-separated tags} 
                            {privacy=unlisted : Privacy setting (public, unlisted, private)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload a video file to YouTube';

    /**
     * Execute the console command.
     */
    public function handle(YouTubeCredentialsService $credentialsService, SimpleYouTubeUploader $uploader)
    {
        // Get arguments
        $filePath = $this->argument('file');
        $title = $this->argument('title');
        $description = $this->argument('description');
        $tagsString = $this->argument('tags');
        $privacy = $this->argument('privacy');

        // File existence check
        if (!file_exists($filePath)) {
            $this->error("The file {$filePath} does not exist.");
            return 1;
        }

        // File type check
        $fileInfo = pathinfo($filePath);
        if (strtolower($fileInfo['extension']) !== 'mp4') {
            $this->error("The file must be an MP4 file.");
            return 1;
        }

        // Check if title is provided, otherwise use filename
        if (empty($title)) {
            $title = $fileInfo['filename'];
            $this->info("Using filename as title: {$title}");
        }

        // Prepare tags
        $tags = [];
        if (!empty($tagsString)) {
            $tags = array_map('trim', explode(',', $tagsString));
        }

        // Check if credentials are available
        $credentials = $credentialsService->loadCredentials();
        if (!$credentials['credentials_available']) {
            $this->error('YouTube credentials are not configured in the .env file.');
            $this->info('Please set one of the following credential sets:');
            $this->line('- OAuth: YOUTUBE_CLIENT_ID, YOUTUBE_CLIENT_SECRET, YOUTUBE_ACCESS_TOKEN, YOUTUBE_REFRESH_TOKEN');
            $this->line('- Simple: YOUTUBE_EMAIL, YOUTUBE_PASSWORD');
            return 1;
        }

        // Set privacy to unlisted if not valid
        if (!in_array($privacy, ['public', 'unlisted', 'private'])) {
            $this->warn("Invalid privacy setting: {$privacy}. Using 'unlisted' instead.");
            $privacy = 'unlisted';
        }

        // Display upload information
        $this->info('Preparing to upload video to YouTube:');
        $this->line("File: {$filePath}");
        $this->line("Title: {$title}");
        $this->line("Privacy: {$privacy}");
        $this->line("Tags: " . (!empty($tags) ? implode(', ', $tags) : 'None'));

        // Start upload process
        $this->info('Starting upload process...');
        try {
            // Upload video
            $result = $uploader->upload([
                'file' => $filePath,
                'title' => $title,
                'description' => $description ?? '',
                'tags' => $tags,
                'privacyStatus' => $privacy,
            ]);

            // Check result
            if (!empty($result['id'])) {
                $videoId = $result['id'];
                $videoUrl = "https://youtu.be/{$videoId}";
                
                $this->info('Upload successful!');
                $this->line("Video ID: {$videoId}");
                $this->line("Video URL: {$videoUrl}");
                
                // Log success
                Log::info("YouTube upload successful. Video ID: {$videoId}, URL: {$videoUrl}");
                
                return 0;
            } else {
                $this->error('Upload failed: No video ID returned.');
                Log::error('YouTube upload failed: No video ID returned', ['result' => $result]);
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Upload failed: ' . $e->getMessage());
            Log::error('YouTube upload exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Provide troubleshooting tips
            $this->line("\nTroubleshooting tips:");
            $this->line("1. Check that your YouTube account is verified and can upload videos");
            $this->line("2. Verify your .env credentials are correct");
            $this->line("3. Check the file format and size (YouTube has limits)");
            $this->line("4. Review the logs for more detailed error information");
            
            return 1;
        }
    }
} 