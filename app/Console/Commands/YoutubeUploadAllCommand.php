<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Services\SimpleYouTubeUploader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class YoutubeUploadAllCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:upload-all {--limit=10 : Maximum number of videos to upload} {--privacy=unlisted : Privacy status (private, unlisted, public)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload all videos to YouTube that have not been uploaded yet';

    /**
     * Execute the console command.
     */
    public function handle(SimpleYouTubeUploader $uploader)
    {
        // Get videos that don't have a YouTube video ID
        $videos = Video::whereNull('youtube_id')
            ->orWhere('youtube_id', '')
            ->orderBy('created_at', 'asc')
            ->limit($this->option('limit'))
            ->get();
            
        $count = $videos->count();
        
        if ($count === 0) {
            $this->info('No videos found to upload.');
            return 0;
        }
        
        $this->info("Found $count videos to upload to YouTube.");
        
        $privacyStatus = $this->option('privacy');
        $successCount = 0;
        $failureCount = 0;
        
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();
        
        foreach ($videos as $video) {
            $this->newLine();
            $this->info("Processing video: {$video->title} (ID: {$video->id})");
            
            $filePath = storage_path('app/public/videos/' . $video->filename);
            
            if (!file_exists($filePath)) {
                $this->error("  File does not exist: $filePath");
                $failureCount++;
                $progressBar->advance();
                continue;
            }
            
            try {
                // Get tags from video
                $tags = [];
                if (!empty($video->tags)) {
                    $tags = explode(',', $video->tags);
                    $tags = array_map('trim', $tags);
                }
                
                // Determine category
                $category = 'Music'; // Default
                
                $videoId = $uploader->upload(
                    $filePath,
                    $video->title,
                    $video->description ?? 'Uploaded from SunoPanel',
                    $tags,
                    $privacyStatus,
                    $category
                );
                
                if ($videoId) {
                    $video->youtube_id = $videoId;
                    $video->save();
                    
                    $this->info("  Upload successful! YouTube Video ID: $videoId");
                    $successCount++;
                } else {
                    $this->error("  Upload failed. No video ID returned.");
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $this->error("  Upload failed with exception: " . $e->getMessage());
                Log::error("YouTube upload failed for video #{$video->id}", [
                    'video_id' => $video->id,
                    'file' => $filePath,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $failureCount++;
            }
            
            $progressBar->advance();
            
            // Sleep for a short time to avoid rate limiting
            sleep(2);
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("YouTube upload process completed:");
        $this->info("  Successful uploads: $successCount");
        $this->info("  Failed uploads: $failureCount");
        
        return ($failureCount === 0) ? 0 : 1;
    }
} 