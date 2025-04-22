<?php

namespace App\Console\Commands;

use App\Jobs\UploadTrackToYouTube;
use App\Models\Track;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class UploadAllVideosToYouTube extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:upload-all {--force : Force re-upload of videos already on YouTube} {--limit= : Limit the number of videos to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload all videos to YouTube using the queue system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $forceReupload = $this->option('force');
        $limit = $this->option('limit');
        
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
        $this->info("Dispatching upload jobs for {$count} tracks to the queue");
        
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
                UploadTrackToYouTube::dispatch($track);
                $successCount++;
                $this->line("Queued track '{$track->title}' (ID: {$track->id}) for upload");
            } catch (\Exception $e) {
                $this->error("Failed to queue track '{$track->title}' (ID: {$track->id}): {$e->getMessage()}");
                Log::error('Failed to queue YouTube upload', [
                    'track_id' => $track->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errorCount++;
            }
        }
        
        $this->newLine();
        $this->info("Upload queuing summary:");
        $this->info("- Successfully queued: {$successCount}");
        $this->info("- Errors: {$errorCount}");
        $this->info("Run your queue worker to process the uploads: php artisan queue:work");
        
        return $errorCount > 0 ? 1 : 0;
    }
} 