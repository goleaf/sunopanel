<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Track;
use App\Models\YouTubeAccount;
use App\Services\YouTubeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class YouTubeBulkUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 1800; // 30 minutes

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly Track $track,
        private readonly YouTubeAccount $account,
        private readonly array $uploadOptions = []
    ) {
        $this->onQueue('youtube-uploads');
    }

    /**
     * Execute the job.
     */
    public function handle(YouTubeService $youtubeService): void
    {
        Log::info('Starting YouTube bulk upload job', [
            'track_id' => $this->track->id,
            'track_title' => $this->track->title,
            'account_id' => $this->account->id,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Set the YouTube account
            if (!$youtubeService->setAccount($this->account)) {
                throw new \Exception('Failed to set YouTube account');
            }

            // Check if track is still eligible for upload
            if (!$this->isTrackEligible()) {
                Log::info('Track no longer eligible for upload', [
                    'track_id' => $this->track->id,
                    'reason' => $this->getIneligibilityReason(),
                ]);
                return;
            }

            // Perform the upload
            $videoId = $youtubeService->uploadVideo(
                $this->track->mp4_file_path,
                $this->track->title,
                $this->generateDescription(),
                $this->generateTags(),
                $this->uploadOptions['privacy_status'] ?? 'unlisted',
                $this->uploadOptions['made_for_kids'] ?? false,
                $this->uploadOptions['is_short'] ?? false,
                $this->uploadOptions['category_id'] ?? '10' // Music category
            );

            if (!$videoId) {
                throw new \Exception('Upload returned no video ID');
            }

            // Update track with YouTube information
            $this->track->update([
                'youtube_video_id' => $videoId,
                'youtube_uploaded_at' => now(),
                'youtube_enabled' => true,
            ]);

            // Update account last used timestamp
            $this->account->update(['last_used_at' => now()]);

            Log::info('Successfully uploaded track to YouTube via bulk job', [
                'track_id' => $this->track->id,
                'video_id' => $videoId,
                'title' => $this->track->title,
                'account_id' => $this->account->id,
            ]);

        } catch (\Exception $e) {
            Log::error('YouTube bulk upload job failed', [
                'track_id' => $this->track->id,
                'account_id' => $this->account->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // If this is the final attempt, mark the track with error
            if ($this->attempts() >= $this->tries) {
                $this->track->update([
                    'error_message' => 'YouTube upload failed: ' . $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('YouTube bulk upload job permanently failed', [
            'track_id' => $this->track->id,
            'account_id' => $this->account->id,
            'error' => $exception->getMessage(),
        ]);

        // Update track with final error message
        $this->track->update([
            'error_message' => 'YouTube upload permanently failed: ' . $exception->getMessage(),
        ]);
    }

    /**
     * Check if the track is still eligible for upload.
     */
    private function isTrackEligible(): bool
    {
        // Refresh the track from database
        $this->track->refresh();

        return $this->track->status === 'completed' &&
               !empty($this->track->mp4_path) &&
               file_exists($this->track->mp4_file_path) &&
               empty($this->track->youtube_video_id) &&
               $this->track->youtube_enabled;
    }

    /**
     * Get the reason why the track is not eligible.
     */
    private function getIneligibilityReason(): string
    {
        $this->track->refresh();

        if ($this->track->status !== 'completed') {
            return 'Track is not completed';
        }

        if (empty($this->track->mp4_path)) {
            return 'No MP4 file path';
        }

        if (!file_exists($this->track->mp4_file_path)) {
            return 'MP4 file does not exist';
        }

        if (!empty($this->track->youtube_video_id)) {
            return 'Already uploaded to YouTube';
        }

        if (!$this->track->youtube_enabled) {
            return 'YouTube upload disabled for this track';
        }

        return 'Unknown reason';
    }

    /**
     * Generate description for YouTube video.
     */
    private function generateDescription(): string
    {
        $description = $this->track->title;
        
        if ($this->track->genres->isNotEmpty()) {
            $description .= "\n\nGenres: " . $this->track->genres_list;
        }

        $description .= "\n\nGenerated by SunoPanel";
        
        return $description;
    }

    /**
     * Generate tags for YouTube video.
     */
    private function generateTags(): array
    {
        $tags = ['music', 'audio'];
        
        if ($this->track->genres->isNotEmpty()) {
            $genreTags = $this->track->genres->pluck('name')->map(function ($genre) {
                return strtolower(str_replace(' ', '', $genre));
            })->toArray();
            
            $tags = array_merge($tags, $genreTags);
        }

        // Limit to 500 characters total and 30 tags max
        $tags = array_slice($tags, 0, 30);
        
        return $tags;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'youtube-upload',
            'track:' . $this->track->id,
            'account:' . $this->account->id,
        ];
    }
} 