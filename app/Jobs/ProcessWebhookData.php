<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Track;
use App\Services\YouTubeService;

final class ProcessWebhookData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $service,
        private readonly array $payload
    ) {
        $this->onQueue('webhook-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing webhook data in background', [
                'service' => $this->service,
                'payload_keys' => array_keys($this->payload),
            ]);

            match ($this->service) {
                'youtube' => $this->processYouTubeWebhook(),
                'suno' => $this->processSunoWebhook(),
                default => $this->processGenericWebhook(),
            };

            Log::info('Webhook data processed successfully', [
                'service' => $this->service,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process webhook data', [
                'service' => $this->service,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Process YouTube webhook data.
     */
    private function processYouTubeWebhook(): void
    {
        $eventType = $this->payload['event_type'] ?? 'unknown';
        $videoId = $this->payload['video_id'] ?? null;

        if (!$videoId) {
            return;
        }

        $track = Track::where('youtube_video_id', $videoId)->first();
        if (!$track) {
            Log::warning('Track not found for YouTube webhook', [
                'video_id' => $videoId,
                'event_type' => $eventType,
            ]);
            return;
        }

        switch ($eventType) {
            case 'analytics_update':
                $this->updateYouTubeAnalytics($track);
                break;
                
            case 'video_published':
                $this->handleVideoPublished($track);
                break;
                
            case 'video_updated':
                $this->handleVideoUpdated($track);
                break;
        }
    }

    /**
     * Process Suno AI webhook data.
     */
    private function processSunoWebhook(): void
    {
        $eventType = $this->payload['event'] ?? $this->payload['type'] ?? 'unknown';
        
        Log::info('Processing Suno webhook in background', [
            'event_type' => $eventType,
            'payload' => $this->payload,
        ]);

        switch ($eventType) {
            case 'track_generated':
                $this->handleSunoTrackGenerated();
                break;
                
            case 'track_updated':
                $this->handleSunoTrackUpdated();
                break;
                
            case 'generation_failed':
                $this->handleSunoGenerationFailed();
                break;
        }
    }

    /**
     * Process generic webhook data.
     */
    private function processGenericWebhook(): void
    {
        Log::info('Processing generic webhook in background', [
            'service' => $this->service,
            'payload' => $this->payload,
        ]);

        // Basic processing for generic webhooks
        // Can be extended based on specific service requirements
    }

    /**
     * Update YouTube analytics for a track.
     */
    private function updateYouTubeAnalytics(Track $track): void
    {
        try {
            $youtubeService = app(YouTubeService::class);
            
            if (!$youtubeService->isAuthenticated()) {
                Log::warning('YouTube service not authenticated for analytics update', [
                    'track_id' => $track->id,
                ]);
                return;
            }

            $analytics = $youtubeService->getVideoAnalytics($track->youtube_video_id);
            
            if ($analytics) {
                $track->update([
                    'youtube_views' => $analytics['views'] ?? $track->youtube_views,
                    'youtube_likes' => $analytics['likes'] ?? $track->youtube_likes,
                    'youtube_comments' => $analytics['comments'] ?? $track->youtube_comments,
                    'youtube_analytics_updated_at' => now(),
                ]);

                Log::info('YouTube analytics updated via webhook', [
                    'track_id' => $track->id,
                    'video_id' => $track->youtube_video_id,
                    'views' => $analytics['views'] ?? 0,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to update YouTube analytics via webhook', [
                'track_id' => $track->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle video published event.
     */
    private function handleVideoPublished(Track $track): void
    {
        $track->update([
            'youtube_status' => 'published',
            'youtube_published_at' => now(),
        ]);

        Log::info('Track marked as published via webhook', [
            'track_id' => $track->id,
            'video_id' => $track->youtube_video_id,
        ]);
    }

    /**
     * Handle video updated event.
     */
    private function handleVideoUpdated(Track $track): void
    {
        $changes = $this->payload['changes'] ?? [];
        
        Log::info('Track video updated via webhook', [
            'track_id' => $track->id,
            'video_id' => $track->youtube_video_id,
            'changes' => $changes,
        ]);

        // Could update track metadata based on changes
        if (isset($changes['title'])) {
            // Handle title change
        }
        
        if (isset($changes['description'])) {
            // Handle description change
        }
    }

    /**
     * Handle Suno track generated event.
     */
    private function handleSunoTrackGenerated(): void
    {
        $trackData = $this->payload['track'] ?? $this->payload;
        
        // Could create a new track from Suno data
        Log::info('Suno track generation completed', [
            'suno_id' => $trackData['id'] ?? null,
            'title' => $trackData['title'] ?? 'Unknown',
            'status' => $trackData['status'] ?? 'unknown',
        ]);
    }

    /**
     * Handle Suno track updated event.
     */
    private function handleSunoTrackUpdated(): void
    {
        $trackData = $this->payload['track'] ?? $this->payload;
        $changes = $this->payload['changes'] ?? [];
        
        Log::info('Suno track updated', [
            'suno_id' => $trackData['id'] ?? null,
            'changes' => $changes,
        ]);
    }

    /**
     * Handle Suno generation failed event.
     */
    private function handleSunoGenerationFailed(): void
    {
        $error = $this->payload['error'] ?? 'Unknown error';
        $sunoId = $this->payload['track_id'] ?? $this->payload['id'] ?? null;
        
        Log::error('Suno track generation failed', [
            'suno_id' => $sunoId,
            'error' => $error,
            'payload' => $this->payload,
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessWebhookData job failed', [
            'service' => $this->service,
            'payload' => $this->payload,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
} 