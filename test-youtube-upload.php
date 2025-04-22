<?php

// Bootstrap Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Track;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get all tracks with MP4 files that haven't been uploaded to YouTube yet
$tracks = Track::whereNotNull('mp4_path')
    ->whereNull('youtube_video_id')
    ->get();

echo "Starting YouTube upload test for " . $tracks->count() . " tracks that haven't been uploaded yet\n";

foreach ($tracks as $track) {
    $trackId = $track->id;
    echo "\n=== Processing track ID: {$trackId} ===\n";

    try {
        echo "Found track: {$track->title}\n";
        
        // Check if track is completed and has an MP4 file
        if ($track->status !== 'completed') {
            echo "Error: Track is not completed (current status: {$track->status}). Skipping.\n";
            continue;
        }
        
        if (empty($track->mp4_path)) {
            echo "Error: Track does not have an MP4 file. Skipping.\n";
            continue;
        }
        
        // Check if file exists
        $videoPath = storage_path('app/public/' . $track->mp4_path);
        if (!file_exists($videoPath)) {
            echo "Error: MP4 file not found at path: {$videoPath}. Skipping.\n";
            continue;
        }
        
        echo "MP4 file found at: {$videoPath}\n";
        echo "File size: " . round(filesize($videoPath) / (1024 * 1024), 2) . " MB\n";
        
        // Get YouTube service
        $youtubeService = app(\App\Services\YouTubeService::class);
        if (!$youtubeService->isAuthenticated()) {
            echo "Error: YouTube service is not authenticated. Please authenticate first.\n";
            exit(1);
        }
        
        echo "YouTube service is authenticated.\n";
        
        // Creating a title with timestamp to make it unique
        $title = $track->title;
        $description = "Track: {$track->title}\nGenres: {$track->genres_string}";
        
        // Prepare tags from genres
        $tags = [];
        if (!empty($track->genres_string)) {
            $tags = array_map('trim', explode(',', $track->genres_string));
        }
        $tags = array_merge($tags, ['sunopanel', 'ai music', 'ai generated', 'test upload']);
        
        echo "Uploading track to YouTube with title: {$title}\n";
        echo "Description: {$description}\n";
        echo "Tags: " . implode(', ', $tags) . "\n";
        
        // Upload video directly using YouTubeService
        try {
            // Set isShort parameter to false to upload as a regular video, not a Short
            $videoId = $youtubeService->uploadVideo(
                $videoPath,
                $title,
                $description,
                $tags,
                'public', // Privacy status
                false, // Not made for kids (audience)
                false  // Not a Short video (regular channel video)
            );
            
            if (!$videoId) {
                echo "Error: Failed to upload video. No video ID returned. Skipping.\n";
                continue;
            }
            
            echo "Video uploaded successfully with ID: {$videoId}\n";
            echo "YouTube URL: https://www.youtube.com/watch?v={$videoId}\n";
            
            // Update track with YouTube ID
            $track->youtube_video_id = $videoId;
            $track->youtube_uploaded_at = now();
            $track->save();
            
            echo "Track updated with YouTube video ID.\n";
            
        } catch (\Exception $e) {
            echo "Error during upload: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            echo "Continuing with next track...\n";
            continue;
        }
        
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        echo "Continuing with next track...\n";
        continue;
    }
}

echo "Test completed successfully.\n"; 