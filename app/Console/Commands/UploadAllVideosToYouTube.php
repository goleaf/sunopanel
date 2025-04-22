<?php

namespace App\Console\Commands;

use App\Models\Track;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

final class UploadAllVideosToYouTube extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:upload-all 
                           {--force : Force re-upload of videos already on YouTube} 
                           {--limit= : Limit the number of videos to process}
                           {--privacy=unlisted : Privacy setting (public, unlisted, private)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload all eligible videos to YouTube';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $forceReupload = $this->option('force');
        $limit = $this->option('limit');
        $privacy = $this->option('privacy');
        
        // Get uploader service
        $uploader = app(\App\Services\SimpleYouTubeUploader::class);
        
        if (!$uploader->isAuthenticated()) {
            $this->error('YouTube service is not authenticated. Please authenticate first.');
            return 1;
        }
        
        $query = Track::whereNotNull('mp4_path')
            ->where('status', 'completed');
            
        if (!$forceReupload) {
            $query->whereNull('youtube_video_id');
        }
        
        if ($limit) {
            $query->limit((int) $limit);
        }
        
        $tracks = $query->get();
        
        if ($tracks->isEmpty()) {
            $this->info('No tracks with videos found that need to be uploaded.');
            return 0;
        }
        
        $count = $tracks->count();
        $this->info("Processing uploads for {$count} tracks");
        
        $successCount = 0;
        $errorCount = 0;
        
        // Create progress bar
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        foreach ($tracks as $track) {
            try {
                // Upload directly using SimpleYouTubeUploader
                $videoId = $uploader->uploadTrack(
                    $track,
                    null, // Default title
                    null, // Default description (just track title)
                    $privacy
                );
                
                if ($videoId) {
                    $successCount++;
                }
                
                // Small delay to avoid rate limits
                usleep(500000); // 0.5 second delay
            } catch (\Exception $e) {
                $this->error("Error uploading track '{$track->title}' (ID: {$track->id}): {$e->getMessage()}");
                Log::error('Error in YouTube upload command', [
                    'track_id' => $track->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errorCount++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("Upload summary:");
        $this->info("- Successfully uploaded: {$successCount}");
        $this->info("- Errors: {$errorCount}");
        
        return $errorCount > 0 ? 1 : 0;
    }
} 