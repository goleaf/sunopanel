<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Track;
use App\Services\TrackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

final class TrackController extends BaseApiController
{
    public function __construct(
        private readonly TrackService $trackService
    ) {}

    /**
     * Get all tracks with pagination and filtering.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'page' => 'integer|min:1',
                'limit' => 'integer|min:1|max:100',
                'status' => ['string', Rule::in(['pending', 'processing', 'completed', 'failed', 'stopped'])],
                'genre' => 'string|exists:genres,slug',
                'search' => 'string|max:255',
                'sort_by' => ['string', Rule::in(['id', 'title', 'status', 'created_at', 'updated_at'])],
                'sort_order' => ['string', Rule::in(['asc', 'desc'])],
            ]);

            $query = Track::with(['genres']);

            // Apply filters
            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (!empty($validated['genre'])) {
                $query->whereHas('genres', function ($q) use ($validated) {
                    $q->where('slug', $validated['genre']);
                });
            }

            if (!empty($validated['search'])) {
                $query->where('title', 'like', '%' . $validated['search'] . '%');
            }

            // Apply sorting
            $sortBy = $validated['sort_by'] ?? 'created_at';
            $sortOrder = $validated['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Paginate
            $limit = min($validated['limit'] ?? $this->defaultLimit, $this->maxLimit);
            $tracks = $query->paginate($limit);

            return $this->paginated($tracks, 'Tracks retrieved successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get a specific track.
     */
    public function show(Track $track): JsonResponse
    {
        try {
            $track->load(['genres']);
            return $this->success($track, 'Track retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get track status.
     */
    public function status(Track $track): JsonResponse
    {
        try {
            $status = [
                'id' => $track->id,
                'title' => $track->title,
                'status' => $track->status,
                'progress' => $track->progress ?? 0,
                'error_message' => $track->error_message,
                'created_at' => $track->created_at,
                'updated_at' => $track->updated_at,
                'processing_started_at' => $track->processing_started_at,
                'processing_completed_at' => $track->processing_completed_at,
                'file_paths' => [
                    'mp3' => $track->mp3_path,
                    'mp4' => $track->mp4_path,
                    'image' => $track->image_path,
                ],
                'youtube' => [
                    'video_id' => $track->youtube_video_id,
                    'uploaded_at' => $track->youtube_uploaded_at,
                ],
            ];

            return $this->success($status, 'Track status retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Start processing a track.
     */
    public function start(Track $track): JsonResponse
    {
        try {
            if ($track->status === 'processing') {
                return $this->error('Track is already being processed', 409);
            }

            if ($track->status === 'completed') {
                return $this->error('Track is already completed', 409);
            }

            $result = $this->trackService->startProcessing($track);

            if ($result) {
                Log::info('Track processing started via API', [
                    'track_id' => $track->id,
                    'title' => $track->title,
                ]);

                return $this->success([
                    'id' => $track->id,
                    'status' => $track->fresh()->status,
                    'message' => 'Track processing started successfully',
                ], 'Track processing started');
            }

            return $this->error('Failed to start track processing');

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Stop processing a track.
     */
    public function stop(int $trackId): JsonResponse
    {
        try {
            $track = Track::findOrFail($trackId);

            if (!in_array($track->status, ['processing', 'pending'])) {
                return $this->error('Track is not currently being processed or pending', 409);
            }

            $result = $this->trackService->stopProcessing($track);

            if ($result) {
                Log::info('Track processing stopped via API', [
                    'track_id' => $track->id,
                    'title' => $track->title,
                ]);

                return $this->success([
                    'id' => $track->id,
                    'status' => $track->fresh()->status,
                    'message' => 'Track processing stopped successfully',
                ], 'Track processing stopped');
            }

            return $this->error('Failed to stop track processing');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Track not found');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Retry processing a track.
     */
    public function retry(Track $track): JsonResponse
    {
        try {
            if ($track->status === 'processing') {
                return $this->error('Track is currently being processed', 409);
            }

            if ($track->status === 'completed') {
                return $this->error('Track is already completed', 409);
            }

            $result = $this->trackService->retryProcessing($track);

            if ($result) {
                Log::info('Track processing retried via API', [
                    'track_id' => $track->id,
                    'title' => $track->title,
                ]);

                return $this->success([
                    'id' => $track->id,
                    'status' => $track->fresh()->status,
                    'message' => 'Track processing retried successfully',
                ], 'Track processing retried');
            }

            return $this->error('Failed to retry track processing');

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get bulk status for multiple tracks.
     */
    public function bulkStatus(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'track_ids' => 'required|array|min:1|max:100',
                'track_ids.*' => 'integer|exists:tracks,id',
            ]);

            $tracks = Track::whereIn('id', $validated['track_ids'])
                ->select(['id', 'title', 'status', 'progress', 'error_message', 'updated_at'])
                ->get();

            $statuses = $tracks->map(function ($track) {
                return [
                    'id' => $track->id,
                    'title' => $track->title,
                    'status' => $track->status,
                    'progress' => $track->progress ?? 0,
                    'error_message' => $track->error_message,
                    'updated_at' => $track->updated_at,
                ];
            });

            return $this->success($statuses, 'Bulk track status retrieved successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Perform bulk actions on tracks.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'action' => ['required', 'string', Rule::in(['start', 'stop', 'retry'])],
                'track_ids' => 'required|array|min:1|max:50',
                'track_ids.*' => 'integer|exists:tracks,id',
            ]);

            $tracks = Track::whereIn('id', $validated['track_ids'])->get();
            $results = [];
            $successCount = 0;

            foreach ($tracks as $track) {
                try {
                    $result = match ($validated['action']) {
                        'start' => $this->trackService->startProcessing($track),
                        'stop' => $this->trackService->stopProcessing($track),
                        'retry' => $this->trackService->retryProcessing($track),
                    };

                    $results[] = [
                        'id' => $track->id,
                        'title' => $track->title,
                        'success' => $result,
                        'status' => $track->fresh()->status,
                    ];

                    if ($result) {
                        $successCount++;
                    }

                } catch (\Exception $e) {
                    $results[] = [
                        'id' => $track->id,
                        'title' => $track->title,
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            Log::info('Bulk track action performed via API', [
                'action' => $validated['action'],
                'total_tracks' => count($tracks),
                'successful' => $successCount,
            ]);

            return $this->success([
                'action' => $validated['action'],
                'total' => count($tracks),
                'successful' => $successCount,
                'failed' => count($tracks) - $successCount,
                'results' => $results,
            ], "Bulk {$validated['action']} action completed");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
} 