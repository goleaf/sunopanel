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

// Track ID to upload
$trackId = 1457;

echo "Starting YouTube upload test for track ID: {$trackId}\n";

try {
    // Find the track
    $track = Track::find($trackId);
    
    if (!$track) {
        echo "Error: Track not found with ID {$trackId}\n";
        exit(1);
    }
    
    echo "Found track: {$track->title}\n";
    
    // Check if track is completed and has an MP4 file
    if ($track->status !== 'completed') {
        echo "Error: Track is not completed (current status: {$track->status})\n";
        exit(1);
    }
    
    if (empty($track->mp4_path)) {
        echo "Error: Track does not have an MP4 file\n";
        exit(1);
    }
    
    // Check if file exists
    $videoPath = storage_path('app/public/' . $track->mp4_path);
    if (!file_exists($videoPath)) {
        echo "Error: MP4 file not found at path: {$videoPath}\n";
        exit(1);
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
    $title = $track->title . ' - Upload Test ' . date('Y-m-d H:i:s');
    $description = "Generated with SunoPanel\nTrack: {$track->title}\nGenres: {$track->genres_string}\nTest upload via script.";
    
    // Prepare tags
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
        $videoId = $youtubeService->uploadVideo(
            $videoPath,
            $title,
            $description,
            $tags,
            'unlisted' // Privacy status
        );
        
        if (!$videoId) {
            echo "Error: Failed to upload video. No video ID returned.\n";
            exit(1);
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
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "Test completed successfully.\n"; 