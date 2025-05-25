<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Track;
use App\Models\YouTubeAccount;
use App\Jobs\YouTubeBulkUploadJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
// use Laravel\Pennant\Feature; // Removed for now

final class YouTubeBulkService
{
    public function __construct(
        private readonly YouTubeService $youtubeService
    ) {}

    /**
     * Queue bulk upload for multiple tracks.
     */
    public function queueBulkUpload(
        Collection $tracks,
        ?YouTubeAccount $account = null,
        array $uploadOptions = []
    ): array {
        // Feature flag check removed for now - can be re-enabled when Pennant is configured

        $account = $account ?? YouTubeAccount::getActive();
        if (!$account) {
            throw new \Exception('No active YouTube account found');
        }

        if (!$account->hasValidTokens()) {
            throw new \Exception('YouTube account has invalid or expired tokens');
        }

        $results = [
            'queued' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($tracks as $track) {
            try {
                if (!$this->canUploadTrack($track)) {
                    $results['skipped']++;
                    $results['errors'][] = "Track {$track->id} cannot be uploaded: " . $this->getUploadBlockReason($track);
                    continue;
                }

                YouTubeBulkUploadJob::dispatch($track, $account, $uploadOptions)
                    ->onQueue('youtube-uploads')
                    ->delay(now()->addSeconds($results['queued'] * 30)); // Stagger uploads

                $results['queued']++;
                
                Log::info('Queued track for YouTube upload', [
                    'track_id' => $track->id,
                    'track_title' => $track->title,
                    'account_id' => $account->id,
                ]);
            } catch (\Exception $e) {
                $results['errors'][] = "Failed to queue track {$track->id}: " . $e->getMessage();
                Log::error('Failed to queue track for YouTube upload', [
                    'track_id' => $track->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Upload multiple tracks synchronously (for smaller batches).
     */
    public function uploadBatch(
        Collection $tracks,
        ?YouTubeAccount $account = null,
        array $uploadOptions = []
    ): array {
        $account = $account ?? YouTubeAccount::getActive();
        if (!$account) {
            throw new \Exception('No active YouTube account found');
        }

        if (!$this->youtubeService->setAccount($account)) {
            throw new \Exception('Failed to set YouTube account');
        }

        $results = [
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'uploads' => [],
            'errors' => [],
        ];

        foreach ($tracks as $track) {
            try {
                if (!$this->canUploadTrack($track)) {
                    $results['skipped']++;
                    $results['errors'][] = "Track {$track->id} skipped: " . $this->getUploadBlockReason($track);
                    continue;
                }

                $videoId = $this->uploadSingleTrack($track, $uploadOptions);
                
                if ($videoId) {
                    $results['successful']++;
                    $results['uploads'][] = [
                        'track_id' => $track->id,
                        'video_id' => $videoId,
                        'title' => $track->title,
                    ];
                    
                    Log::info('Successfully uploaded track to YouTube', [
                        'track_id' => $track->id,
                        'video_id' => $videoId,
                        'title' => $track->title,
                    ]);
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to upload track {$track->id}: Unknown error";
                }

                // Add delay between uploads to avoid rate limiting
                sleep(5);
                
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Track {$track->id} failed: " . $e->getMessage();
                
                Log::error('Failed to upload track to YouTube', [
                    'track_id' => $track->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Upload a single track with retry logic.
     */
    public function uploadSingleTrack(Track $track, array $options = []): ?string
    {
        $maxRetries = $options['max_retries'] ?? 3;
        $retryDelay = $options['retry_delay'] ?? 5;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $videoId = $this->youtubeService->uploadVideo(
                    $track->mp4_file_path,
                    $track->title,
                    $this->generateDescription($track),
                    $this->generateTags($track),
                    $options['privacy_status'] ?? 'unlisted',
                    $options['made_for_kids'] ?? false,
                    $options['is_short'] ?? false,
                    $options['category_id'] ?? '10' // Music category
                );

                if ($videoId) {
                    // Update track with YouTube information
                    $track->update([
                        'youtube_video_id' => $videoId,
                        'youtube_uploaded_at' => now(),
                        'youtube_enabled' => true,
                    ]);

                    return $videoId;
                }
            } catch (\Exception $e) {
                Log::warning("Upload attempt {$attempt} failed for track {$track->id}", [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                ]);

                if ($attempt < $maxRetries) {
                    sleep($retryDelay * $attempt); // Exponential backoff
                } else {
                    throw $e; // Re-throw on final attempt
                }
            }
        }

        return null;
    }

    /**
     * Check if a track can be uploaded to YouTube.
     */
    public function canUploadTrack(Track $track): bool
    {
        return $track->status === 'completed' &&
               !empty($track->mp4_path) &&
               file_exists($track->mp4_file_path) &&
               empty($track->youtube_video_id) &&
               $track->youtube_enabled;
    }

    /**
     * Get the reason why a track cannot be uploaded.
     */
    public function getUploadBlockReason(Track $track): string
    {
        if ($track->status !== 'completed') {
            return 'Track is not completed';
        }

        if (empty($track->mp4_path)) {
            return 'No MP4 file path';
        }

        if (!file_exists($track->mp4_file_path)) {
            return 'MP4 file does not exist';
        }

        if (!empty($track->youtube_video_id)) {
            return 'Already uploaded to YouTube';
        }

        if (!$track->youtube_enabled) {
            return 'YouTube upload disabled for this track';
        }

        return 'Unknown reason';
    }

    /**
     * Generate description for YouTube video.
     */
    private function generateDescription(Track $track): string
    {
        $description = $track->title;
        
        if ($track->genres->isNotEmpty()) {
            $description .= "\n\nGenres: " . $track->genres_list;
        }

        $description .= "\n\nGenerated by SunoPanel";
        
        return $description;
    }

    /**
     * Generate tags for YouTube video.
     */
    private function generateTags(Track $track): array
    {
        $tags = ['music', 'audio'];
        
        if ($track->genres->isNotEmpty()) {
            $genreTags = $track->genres->pluck('name')->map(function ($genre) {
                return strtolower(str_replace(' ', '', $genre));
            })->toArray();
            
            $tags = array_merge($tags, $genreTags);
        }

        // Limit to 500 characters total and 30 tags max
        $tags = array_slice($tags, 0, 30);
        
        return $tags;
    }

    /**
     * Get tracks eligible for bulk upload.
     */
    public function getEligibleTracks(int $limit = 50): Collection
    {
        return Track::where('status', 'completed')
            ->whereNotNull('mp4_path')
            ->whereNull('youtube_video_id')
            ->where('youtube_enabled', true)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get upload queue status.
     */
    public function getQueueStatus(): array
    {
        try {
            $pendingJobs = Queue::size('youtube-uploads');
        } catch (\Exception $e) {
            $pendingJobs = 0;
        }

        // For failed jobs, we'll use a simpler approach since Redis doesn't have a failed_jobs table
        $failedJobs = 0;
        try {
            // If using database queue driver, we can check failed_jobs table
            if (config('queue.default') === 'database') {
                $failedJobs = \DB::table('failed_jobs')
                    ->where('queue', 'youtube-uploads')
                    ->count();
            }
        } catch (\Exception $e) {
            // Ignore errors for queue status - not critical
        }

        return [
            'pending' => $pendingJobs,
            'processing' => 0, // Would need more complex logic to track processing jobs
            'completed' => 0, // Would need to track completed uploads
            'failed' => $failedJobs,
        ];
    }

    /**
     * Retry failed uploads.
     */
    public function retryFailedUploads(): int
    {
        $retried = 0;
        
        try {
            // Only works with database queue driver
            if (config('queue.default') === 'database') {
                $failedJobs = \DB::table('failed_jobs')
                    ->where('queue', 'youtube-uploads')
                    ->get();

                foreach ($failedJobs as $failedJob) {
                    try {
                        // Delete from failed jobs
                        \DB::table('failed_jobs')
                            ->where('id', $failedJob->id)
                            ->delete();
                        
                        // Re-queue the job
                        Queue::pushRaw($failedJob->payload, 'youtube-uploads');
                        
                        $retried++;
                    } catch (\Exception $e) {
                        Log::error('Failed to retry YouTube upload job', [
                            'job_id' => $failedJob->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to retry uploads', [
                'error' => $e->getMessage(),
            ]);
        }

        return $retried;
    }
} 