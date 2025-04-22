<?php

// Bootstrap Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Track;
use Illuminate\Support\Facades\Log;

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Check if a YouTube error response indicates quota exceeded
 * 
 * @param string $errorMessage The error message from YouTube API
 * @return bool True if quota has been exceeded
 */
function isQuotaExceeded(string $errorMessage): bool 
{
    $quotaPatterns = [
        'quota exceeded',
        'uploadLimitExceeded',
        'exceeded the number',
        'exceeded your YouTube',
        'exceeds upload'
    ];
    
    foreach ($quotaPatterns as $pattern) {
        if (stripos($errorMessage, $pattern) !== false) {
            return true;
        }
    }
    
    return false;
}

echo "Starting YouTube upload test\n";

// Get the YouTube service
$youtubeService = app(\App\Services\YouTubeService::class);

// Check if authenticated
if (!$youtubeService->isAuthenticated()) {
    echo "ERROR: YouTube service is not authenticated. Please authenticate first.\n";
    echo "Visit /youtube/auth in your browser to authenticate with YouTube.\n";
    exit(1);
}

echo "YouTube service is authenticated. Checking for tracks to upload...\n";

// Get all tracks with MP4 files that haven't been uploaded to YouTube yet
$tracks = Track::whereNotNull('mp4_path')
    ->whereNull('youtube_video_id')
    ->limit(5) // Limit to 5 tracks for initial testing
    ->get();

echo "Found " . $tracks->count() . " tracks that haven't been uploaded yet\n";

$successCount = 0;
$failedCount = 0;
$quotaExceeded = false;

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
        
        // Creating a title with timestamp to make it unique
        $title = $track->title . ' - ' . now()->format('Y-m-d H:i:s');
        $description = "Track: {$track->title}\nGenres: {$track->genres_string}";
        
        // Prepare tags from genres
        $tags = [];
        if (!empty($track->genres_string)) {
            $tags = array_map('trim', explode(',', $track->genres_string));
        }
        $tags = array_merge($tags, ['ai music', 'ai generated', 'test upload']);
        
        echo "Uploading track to YouTube with title: {$title}\n";
        echo "Description: {$description}\n";
        echo "Tags: " . implode(', ', $tags) . "\n";
        
        // Use SimpleYouTubeUploader for uploads
        $uploader = app(\App\Services\SimpleYouTubeUploader::class);
        
        // Check if uploader is authenticated
        if (!$uploader->isAuthenticated()) {
            echo "ERROR: YouTube uploader is not authenticated. Please authenticate first.\n";
            echo "Visit /youtube/auth in your browser to authenticate with YouTube.\n";
            exit(1);
        }
        
        // Upload the track
        $videoId = $uploader->uploadTrack(
            $track,
            $title,
            $description,
            'public',   // Privacy status
            true,       // Add to playlist
            false,      // Not a Short
            false       // Not made for kids
        );
        
        if (!$videoId) {
            echo "Error: Failed to upload video. No video ID returned. Skipping.\n";
            $failedCount++;
            continue;
        }
        
        echo "Video uploaded successfully with ID: {$videoId}\n";
        echo "YouTube URL: https://www.youtube.com/watch?v={$videoId}\n";
        $successCount++;
        
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
        echo "Error during upload: " . $errorMessage . "\n";
        
        // Check if this is a quota exceeded error
        if (isQuotaExceeded($errorMessage)) {
            echo "\n=== YOUTUBE QUOTA EXCEEDED ===\n";
            echo "Your YouTube account has reached its upload limit. You can try:\n";
            echo "1. Wait 24 hours for quotas to reset\n";
            echo "2. Verify your YouTube account if not already verified\n";
            echo "3. Use a different YouTube account\n";
            echo "4. Contact YouTube support if you need a higher quota\n";
            
            // Set quota exceeded flag and exit the loop
            $quotaExceeded = true;
            break;
        }
        
        // For authentication errors, stop processing
        if (strpos($errorMessage, 'authentication') !== false || 
            strpos($errorMessage, 'Login Required') !== false) {
            echo "Authentication error. Please re-authenticate with YouTube.\n";
            exit(1);
        }
        
        echo "Continuing with next track...\n";
        $failedCount++;
    }
}

echo "\n=== Upload Summary ===\n";
echo "Successful uploads: {$successCount}\n";
echo "Failed uploads: {$failedCount}\n";

if ($quotaExceeded) {
    echo "Uploads stopped due to YouTube quota limitations.\n";
}

echo "Test completed.\n"; 