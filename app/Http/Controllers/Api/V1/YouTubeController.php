<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Track;
use App\Services\YouTubeBulkService;
use App\Services\YouTubeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

final class YouTubeController extends BaseApiController
{
    public function __construct(
        private readonly YouTubeService $youtubeService,
        private readonly YouTubeBulkService $youtubeBulkService
    ) {}

    /**
     * Upload a single track to YouTube.
     */
    public function upload(Track $track): JsonResponse
    {
        try {
            if ($track->status !== 'completed') {
                return $this->error('Track must be completed before uploading to YouTube', 422);
            }

            if ($track->youtube_video_id) {
                return $this->error('Track is already uploaded to YouTube', 409);
            }

            $result = $this->youtubeService->uploadVideo($track);

            if ($result['success']) {
                Log::info('Track uploaded to YouTube via API', [
                    'track_id' => $track->id,
                    'video_id' => $result['video_id'],
                ]);

                return $this->success([
                    'track_id' => $track->id,
                    'video_id' => $result['video_id'],
                    'video_url' => "https://www.youtube.com/watch?v={$result['video_id']}",
                    'message' => 'Track uploaded to YouTube successfully',
                ], 'YouTube upload successful');
            }

            return $this->error($result['error'] ?? 'Failed to upload track to YouTube');

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get YouTube upload status for a track.
     */
    public function status(Track $track): JsonResponse
    {
        try {
            $status = [
                'track_id' => $track->id,
                'title' => $track->title,
                'youtube_video_id' => $track->youtube_video_id,
                'youtube_uploaded_at' => $track->youtube_uploaded_at,
                'is_uploaded' => !is_null($track->youtube_video_id),
                'video_url' => $track->youtube_video_id 
                    ? "https://www.youtube.com/watch?v={$track->youtube_video_id}" 
                    : null,
            ];

            return $this->success($status, 'YouTube status retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Start bulk upload to YouTube.
     */
    public function bulkUpload(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'track_ids' => 'array|min:1|max:50',
                'track_ids.*' => 'integer|exists:tracks,id',
                'upload_type' => ['string', Rule::in(['sync', 'queue'])],
                'delay_between_uploads' => 'integer|min:10|max:300', // 10 seconds to 5 minutes
            ]);

            // If no track IDs provided, get all eligible tracks
            if (empty($validated['track_ids'])) {
                $tracks = Track::where('status', 'completed')
                    ->whereNull('youtube_video_id')
                    ->get();
            } else {
                $tracks = Track::whereIn('id', $validated['track_ids'])
                    ->where('status', 'completed')
                    ->whereNull('youtube_video_id')
                    ->get();
            }

            if ($tracks->isEmpty()) {
                return $this->error('No eligible tracks found for YouTube upload', 422);
            }

            $uploadType = $validated['upload_type'] ?? 'queue';
            $delay = $validated['delay_between_uploads'] ?? 30;

            if ($uploadType === 'sync') {
                // Synchronous upload (for small batches)
                if ($tracks->count() > 10) {
                    return $this->error('Synchronous upload is limited to 10 tracks. Use queue upload for larger batches.', 422);
                }

                $result = $this->youtubeBulkService->uploadBatch($tracks, $delay);
            } else {
                // Queue-based upload
                $result = $this->youtubeBulkService->queueBulkUpload($tracks, $delay);
            }

            Log::info('Bulk YouTube upload initiated via API', [
                'upload_type' => $uploadType,
                'track_count' => $tracks->count(),
                'delay' => $delay,
            ]);

            return $this->success([
                'upload_type' => $uploadType,
                'total_tracks' => $tracks->count(),
                'delay_between_uploads' => $delay,
                'tracks' => $tracks->map(function ($track) {
                    return [
                        'id' => $track->id,
                        'title' => $track->title,
                    ];
                }),
                'estimated_completion' => $uploadType === 'queue' 
                    ? now()->addSeconds($tracks->count() * ($delay + 60))->toISOString()
                    : null,
            ], 'Bulk YouTube upload initiated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get bulk upload queue status.
     */
    public function queueStatus(): JsonResponse
    {
        try {
            $status = $this->youtubeBulkService->getQueueStatus();

            return $this->success($status, 'Queue status retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Retry failed YouTube uploads.
     */
    public function retryFailed(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'track_ids' => 'array|max:20',
                'track_ids.*' => 'integer|exists:tracks,id',
            ]);

            $result = $this->youtubeBulkService->retryFailedUploads(
                $validated['track_ids'] ?? []
            );

            Log::info('YouTube upload retry initiated via API', [
                'retry_count' => $result['retry_count'],
                'track_ids' => $validated['track_ids'] ?? 'all_failed',
            ]);

            return $this->success($result, 'Failed uploads retry initiated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get YouTube account information.
     */
    public function accountInfo(): JsonResponse
    {
        try {
            $account = $this->youtubeService->getActiveAccount();

            if (!$account) {
                return $this->error('No active YouTube account found', 404);
            }

            $info = [
                'id' => $account->id,
                'channel_id' => $account->channel_id,
                'channel_title' => $account->channel_title,
                'channel_description' => $account->channel_description,
                'subscriber_count' => $account->subscriber_count,
                'video_count' => $account->video_count,
                'view_count' => $account->view_count,
                'is_active' => $account->is_active,
                'last_used_at' => $account->last_used_at,
                'created_at' => $account->created_at,
            ];

            return $this->success($info, 'YouTube account information retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get upload statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_tracks' => Track::count(),
                'completed_tracks' => Track::where('status', 'completed')->count(),
                'uploaded_to_youtube' => Track::whereNotNull('youtube_video_id')->count(),
                'pending_upload' => Track::where('status', 'completed')
                    ->whereNull('youtube_video_id')
                    ->count(),
                'upload_success_rate' => $this->calculateUploadSuccessRate(),
                'recent_uploads' => Track::whereNotNull('youtube_video_id')
                    ->where('youtube_uploaded_at', '>=', now()->subDays(7))
                    ->count(),
            ];

            return $this->success($stats, 'YouTube statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Calculate upload success rate.
     */
    private function calculateUploadSuccessRate(): float
    {
        $totalCompleted = Track::where('status', 'completed')->count();
        
        if ($totalCompleted === 0) {
            return 0.0;
        }

        $uploaded = Track::whereNotNull('youtube_video_id')->count();
        
        return round(($uploaded / $totalCompleted) * 100, 2);
    }
} 