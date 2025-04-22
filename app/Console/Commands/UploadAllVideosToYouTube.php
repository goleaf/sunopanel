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
        
        $query = Track::whereNotNull('mp4_file')
            ->where('mp4_file', '!=', '');
            
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
        
        foreach ($tracks as $track) {
            $videoPath = storage_path('app/public/videos/' . $track->mp4_file);
            
            if (!file_exists($videoPath)) {
                $this->warn("Video file not found for track '{$track->title}' (ID: {$track->id}). Skipping.");
                $errorCount++;
                continue;
            }
            
            try {
                $this->line("Uploading track '{$track->title}' (ID: {$track->id})...");
                
                // Execute the upload command for each track
                $exitCode = Artisan::call('youtube:upload', [
                    '--track_id' => $track->id,
                    '--title' => $track->title,
                    '--description' => "Generated with SunoPanel\nTrack: {$track->title}",
                    '--privacy' => $privacy
                ]);
                
                if ($exitCode === 0) {
                    $successCount++;
                    $this->info("Successfully uploaded track '{$track->title}' (ID: {$track->id})");
                } else {
                    $output = Artisan::output();
                    $this->error("Failed to upload track '{$track->title}' (ID: {$track->id}): {$output}");
                    $errorCount++;
                }
                
                // Add a small delay to avoid API rate limits
                sleep(2);
            } catch (\Exception $e) {
                $this->error("Error uploading track '{$track->title}' (ID: {$track->id}): {$e->getMessage()}");
                Log::error('Error in YouTube upload command', [
                    'track_id' => $track->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errorCount++;
            }
        }
        
        $this->newLine();
        $this->info("Upload summary:");
        $this->info("- Successfully uploaded: {$successCount}");
        $this->info("- Errors: {$errorCount}");
        
        return $errorCount > 0 ? 1 : 0;
    }
} 