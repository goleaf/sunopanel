<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Track;
use App\Models\WebhookLog;
use App\Jobs\ProcessWebhookData;

final class WebhookService
{
    private const CACHE_PREFIX = 'webhook_stats:';
    private const STATS_TTL = 300; // 5 minutes

    /**
     * Validate YouTube webhook signature.
     */
    public function validateYouTubeSignature(Request $request): bool
    {
        $secret = config('services.youtube.webhook_secret');
        
        if (!$secret) {
            // If no secret is configured, allow all requests (development mode)
            return true;
        }

        $signature = $request->header('X-Hub-Signature-256');
        if (!$signature) {
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate Suno AI webhook signature.
     */
    public function validateSunoSignature(Request $request): bool
    {
        $secret = config('services.suno.webhook_secret');
        
        if (!$secret) {
            // If no secret is configured, allow all requests (development mode)
            return true;
        }

        $signature = $request->header('X-Suno-Signature');
        if (!$signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate generic webhook signature.
     */
    public function validateGenericSignature(Request $request, string $service): bool
    {
        $secret = config("services.{$service}.webhook_secret");
        
        if (!$secret) {
            // If no secret is configured, allow all requests (development mode)
            return true;
        }

        $signature = $request->header('X-Webhook-Signature') ?? $request->header('X-Hub-Signature-256');
        if (!$signature) {
            return false;
        }

        // Handle different signature formats
        $signatureValue = str_starts_with($signature, 'sha256=') 
            ? substr($signature, 7) 
            : $signature;

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);
        
        return hash_equals($expectedSignature, $signatureValue);
    }

    /**
     * Process YouTube webhook data.
     */
    public function processYouTubeWebhook(array $payload): array
    {
        try {
            $this->logWebhook('youtube', $payload);

            // Handle different YouTube webhook events
            $eventType = $payload['event_type'] ?? 'unknown';
            $processed = false;

            switch ($eventType) {
                case 'video_published':
                    $processed = $this->handleVideoPublished($payload);
                    break;
                    
                case 'video_updated':
                    $processed = $this->handleVideoUpdated($payload);
                    break;
                    
                case 'video_deleted':
                    $processed = $this->handleVideoDeleted($payload);
                    break;
                    
                case 'analytics_update':
                    $processed = $this->handleAnalyticsUpdate($payload);
                    break;
                    
                default:
                    Log::info('Unknown YouTube webhook event type', [
                        'event_type' => $eventType,
                        'payload' => $payload,
                    ]);
            }

            // Dispatch background job for heavy processing
            if ($processed && isset($payload['video_id'])) {
                ProcessWebhookData::dispatch('youtube', $payload);
            }

            $this->updateWebhookStats('youtube', $processed);

            return [
                'processed' => $processed,
                'event_type' => $eventType,
                'timestamp' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            Log::error('YouTube webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            $this->updateWebhookStats('youtube', false);
            throw $e;
        }
    }

    /**
     * Process Suno AI webhook data.
     */
    public function processSunoWebhook(array $payload): array
    {
        try {
            $this->logWebhook('suno', $payload);

            // Handle different Suno webhook events
            $eventType = $payload['event'] ?? $payload['type'] ?? 'unknown';
            $processed = false;

            switch ($eventType) {
                case 'track_generated':
                    $processed = $this->handleTrackGenerated($payload);
                    break;
                    
                case 'track_updated':
                    $processed = $this->handleTrackUpdated($payload);
                    break;
                    
                case 'generation_failed':
                    $processed = $this->handleGenerationFailed($payload);
                    break;
                    
                default:
                    Log::info('Unknown Suno webhook event type', [
                        'event_type' => $eventType,
                        'payload' => $payload,
                    ]);
            }

            // Dispatch background job for heavy processing
            if ($processed && isset($payload['track_id'])) {
                ProcessWebhookData::dispatch('suno', $payload);
            }

            $this->updateWebhookStats('suno', $processed);

            return [
                'processed' => $processed,
                'event_type' => $eventType,
                'timestamp' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            Log::error('Suno webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            $this->updateWebhookStats('suno', false);
            throw $e;
        }
    }

    /**
     * Process generic webhook data.
     */
    public function processGenericWebhook(string $service, array $payload): array
    {
        try {
            $this->logWebhook($service, $payload);

            // Basic processing for generic webhooks
            $eventType = $payload['event'] ?? $payload['type'] ?? 'unknown';
            $processed = true; // Assume success for generic webhooks

            Log::info('Generic webhook processed', [
                'service' => $service,
                'event_type' => $eventType,
                'payload_keys' => array_keys($payload),
            ]);

            // Dispatch background job for heavy processing
            ProcessWebhookData::dispatch($service, $payload);

            $this->updateWebhookStats($service, $processed);

            return [
                'processed' => $processed,
                'event_type' => $eventType,
                'service' => $service,
                'timestamp' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            Log::error('Generic webhook processing failed', [
                'service' => $service,
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            $this->updateWebhookStats($service, false);
            throw $e;
        }
    }

    /**
     * Get webhook statistics.
     */
    public function getWebhookStats(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'all';
        
        return Cache::remember($cacheKey, self::STATS_TTL, function () {
            $stats = [];
            
            // Get stats for each service
            $services = ['youtube', 'suno', 'generic'];
            
            foreach ($services as $service) {
                $stats[$service] = $this->getServiceStats($service);
            }
            
            // Calculate totals
            $stats['total'] = [
                'received' => array_sum(array_column($stats, 'received')),
                'processed' => array_sum(array_column($stats, 'processed')),
                'failed' => array_sum(array_column($stats, 'failed')),
                'success_rate' => 0,
            ];
            
            if ($stats['total']['received'] > 0) {
                $stats['total']['success_rate'] = round(
                    ($stats['total']['processed'] / $stats['total']['received']) * 100, 
                    2
                );
            }
            
            return $stats;
        });
    }

    /**
     * Handle video published event.
     */
    private function handleVideoPublished(array $payload): bool
    {
        $videoId = $payload['video_id'] ?? null;
        if (!$videoId) {
            return false;
        }

        // Find track by YouTube video ID and update status
        $track = Track::where('youtube_video_id', $videoId)->first();
        if ($track) {
            $track->update([
                'youtube_uploaded_at' => now(),
                'youtube_status' => 'published',
            ]);
            
            Log::info('Track YouTube status updated to published', [
                'track_id' => $track->id,
                'video_id' => $videoId,
            ]);
            
            return true;
        }

        return false;
    }

    /**
     * Handle video updated event.
     */
    private function handleVideoUpdated(array $payload): bool
    {
        $videoId = $payload['video_id'] ?? null;
        if (!$videoId) {
            return false;
        }

        // Find track and update metadata if needed
        $track = Track::where('youtube_video_id', $videoId)->first();
        if ($track) {
            Log::info('Track YouTube video updated', [
                'track_id' => $track->id,
                'video_id' => $videoId,
                'changes' => $payload['changes'] ?? [],
            ]);
            
            return true;
        }

        return false;
    }

    /**
     * Handle video deleted event.
     */
    private function handleVideoDeleted(array $payload): bool
    {
        $videoId = $payload['video_id'] ?? null;
        if (!$videoId) {
            return false;
        }

        // Find track and clear YouTube data
        $track = Track::where('youtube_video_id', $videoId)->first();
        if ($track) {
            $track->update([
                'youtube_video_id' => null,
                'youtube_uploaded_at' => null,
                'youtube_status' => null,
            ]);
            
            Log::info('Track YouTube data cleared due to video deletion', [
                'track_id' => $track->id,
                'video_id' => $videoId,
            ]);
            
            return true;
        }

        return false;
    }

    /**
     * Handle analytics update event.
     */
    private function handleAnalyticsUpdate(array $payload): bool
    {
        $videoId = $payload['video_id'] ?? null;
        if (!$videoId) {
            return false;
        }

        // Find track and trigger analytics update
        $track = Track::where('youtube_video_id', $videoId)->first();
        if ($track) {
            Log::info('YouTube analytics update triggered by webhook', [
                'track_id' => $track->id,
                'video_id' => $videoId,
            ]);
            
            // Could dispatch a job to update analytics here
            return true;
        }

        return false;
    }

    /**
     * Handle track generated event from Suno.
     */
    private function handleTrackGenerated(array $payload): bool
    {
        $trackData = $payload['track'] ?? $payload;
        $sunoId = $trackData['id'] ?? null;
        
        if (!$sunoId) {
            return false;
        }

        // Create or update track from Suno data
        Log::info('New track generated by Suno AI', [
            'suno_id' => $sunoId,
            'title' => $trackData['title'] ?? 'Unknown',
        ]);

        // Could create a new track here or update existing one
        return true;
    }

    /**
     * Handle track updated event from Suno.
     */
    private function handleTrackUpdated(array $payload): bool
    {
        $trackData = $payload['track'] ?? $payload;
        $sunoId = $trackData['id'] ?? null;
        
        if (!$sunoId) {
            return false;
        }

        Log::info('Track updated by Suno AI', [
            'suno_id' => $sunoId,
            'changes' => $payload['changes'] ?? [],
        ]);

        return true;
    }

    /**
     * Handle generation failed event from Suno.
     */
    private function handleGenerationFailed(array $payload): bool
    {
        $error = $payload['error'] ?? 'Unknown error';
        $sunoId = $payload['track_id'] ?? $payload['id'] ?? null;
        
        Log::error('Suno AI track generation failed', [
            'suno_id' => $sunoId,
            'error' => $error,
        ]);

        return true;
    }

    /**
     * Log webhook data.
     */
    private function logWebhook(string $service, array $payload): void
    {
        try {
            WebhookLog::create([
                'service' => $service,
                'payload' => $payload,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'received_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log webhook', [
                'service' => $service,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update webhook statistics.
     */
    private function updateWebhookStats(string $service, bool $processed): void
    {
        $cacheKey = self::CACHE_PREFIX . $service;
        
        $stats = Cache::get($cacheKey, [
            'received' => 0,
            'processed' => 0,
            'failed' => 0,
            'success_rate' => 0,
        ]);
        
        $stats['received']++;
        if ($processed) {
            $stats['processed']++;
        } else {
            $stats['failed']++;
        }
        
        $stats['success_rate'] = $stats['received'] > 0 
            ? round(($stats['processed'] / $stats['received']) * 100, 2)
            : 0;
        
        Cache::put($cacheKey, $stats, self::STATS_TTL);
    }

    /**
     * Get statistics for a specific service.
     */
    private function getServiceStats(string $service): array
    {
        $cacheKey = self::CACHE_PREFIX . $service;
        
        return Cache::get($cacheKey, [
            'received' => 0,
            'processed' => 0,
            'failed' => 0,
            'success_rate' => 0,
        ]);
    }
} 