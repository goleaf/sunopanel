<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Services\SimpleYouTubeUploader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UploadVideosToYouTube extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:upload-all 
                            {--limit= : Maximum number of videos to upload}
                            {--privacy=unlisted : Privacy setting for the videos (public, private, unlisted)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload all pending videos to YouTube';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(SimpleYouTubeUploader $uploader): int
    {
        $limit = $this->option('limit');
        $privacy = $this->option('privacy');
        
        $this->info('Starting YouTube upload process...');
        
        // Get videos that haven't been uploaded to YouTube yet
        $query = Video::whereNull('youtube_id')
            ->where('processed', true)
            ->orderBy('created_at', 'asc');
        
        if ($limit) {
            $query->limit((int) $limit);
        }
        
        $pendingVideos = $query->get();
        
        $this->info("Found {$pendingVideos->count()} videos to upload.");
        
        if ($pendingVideos->isEmpty()) {
            $this->info('No pending videos to upload.');
            return 0;
        }
        
        $successCount = 0;
        $failCount = 0;
        
        foreach ($pendingVideos as $video) {
            $this->info("Processing video ID: {$video->id}, Title: {$video->title}");
            
            try {
                $videoPath = storage_path('app/public/videos/' . $video->filename);
                
                if (!file_exists($videoPath)) {
                    $this->error("Video file not found: {$videoPath}");
                    $failCount++;
                    continue;
                }
                
                // Process tags
                $tags = $video->tags ? explode(',', $video->tags) : [];
                
                // Upload to YouTube
                $youtubeId = $uploader->upload(
                    $videoPath,
                    $video->title,
                    $video->description ?? '',
                    $tags,
                    $privacy,
                    'Music' // Default category
                );
                
                if ($youtubeId) {
                    // Update the video record with YouTube ID
                    $video->youtube_id = $youtubeId;
                    $video->save();
                    
                    $this->info("✓ Successfully uploaded '{$video->title}' to YouTube. ID: {$youtubeId}");
                    $successCount++;
                } else {
                    $this->error("✗ Failed to upload '{$video->title}' to YouTube.");
                    $failCount++;
                }
                
                // Add a small delay between uploads to avoid rate limiting
                sleep(2);
                
            } catch (\Exception $e) {
                $this->error("Error uploading video {$video->id}: " . $e->getMessage());
                Log::error("YouTube upload error for video {$video->id}: " . $e->getMessage(), [
                    'exception' => $e
                ]);
                $failCount++;
            }
        }
        
        $this->info("YouTube upload process completed.");
        $this->info("Summary: {$successCount} videos uploaded successfully, {$failCount} failed.");
        
        return $failCount > 0 ? 1 : 0;
    }
} 