<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class QueueController extends Controller
{
    public function __construct(
        private readonly QueueService $queueService
    ) {}

    /**
     * Display queue dashboard.
     */
    public function index(): View
    {
        $statistics = $this->queueService->getQueueStatistics();
        $health = $this->queueService->getQueueHealth();
        $activeBatches = $this->queueService->getActiveBatches();
        $availableQueues = $this->queueService->getAvailableQueues();

        return view('queue.index', compact(
            'statistics',
            'health',
            'activeBatches',
            'availableQueues'
        ));
    }

    /**
     * Get queue statistics as JSON.
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->queueService->getQueueStatistics();
            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get queue statistics', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve queue statistics',
            ], 500);
        }
    }

    /**
     * Get queue health status.
     */
    public function health(): JsonResponse
    {
        try {
            $health = $this->queueService->getQueueHealth();
            return response()->json([
                'success' => true,
                'data' => $health,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get queue health', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve queue health',
            ], 500);
        }
    }

    /**
     * Get active batches.
     */
    public function batches(): JsonResponse
    {
        try {
            $batches = $this->queueService->getActiveBatches();
            return response()->json([
                'success' => true,
                'data' => $batches,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get active batches', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve active batches',
            ], 500);
        }
    }

    /**
     * Cancel a batch.
     */
    public function cancelBatch(Request $request, string $batchId): JsonResponse
    {
        try {
            $success = $this->queueService->cancelBatch($batchId);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Batch cancelled successfully',
                ]);
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to cancel batch or batch not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to cancel batch', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to cancel batch',
            ], 500);
        }
    }

    /**
     * Retry failed jobs in a batch.
     */
    public function retryBatch(Request $request, string $batchId): JsonResponse
    {
        try {
            $success = $this->queueService->retryBatchFailedJobs($batchId);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Batch retry initiated successfully',
                ]);
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retry batch or batch not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retry batch', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retry batch',
            ], 500);
        }
    }

    /**
     * Clear failed jobs.
     */
    public function clearFailedJobs(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'job_ids' => 'array',
                'job_ids.*' => 'string',
            ]);
            
            $count = $this->queueService->clearFailedJobs($validated['job_ids'] ?? []);
            
            return response()->json([
                'success' => true,
                'message' => "Cleared {$count} failed jobs",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear failed jobs', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to clear failed jobs',
            ], 500);
        }
    }

    /**
     * Retry failed jobs.
     */
    public function retryFailedJobs(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'job_ids' => 'array',
                'job_ids.*' => 'string',
            ]);
            
            $count = $this->queueService->retryFailedJobs($validated['job_ids'] ?? []);
            
            return response()->json([
                'success' => true,
                'message' => "Retried {$count} failed jobs",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retry failed jobs', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to retry failed jobs',
            ], 500);
        }
    }

    /**
     * Pause a queue.
     */
    public function pauseQueue(Request $request, string $queueName): JsonResponse
    {
        try {
            $success = $this->queueService->pauseQueue($queueName);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => "Queue '{$queueName}' paused successfully",
                ]);
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to pause queue',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to pause queue', [
                'queue' => $queueName,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to pause queue',
            ], 500);
        }
    }

    /**
     * Resume a queue.
     */
    public function resumeQueue(Request $request, string $queueName): JsonResponse
    {
        try {
            $success = $this->queueService->resumeQueue($queueName);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => "Queue '{$queueName}' resumed successfully",
                ]);
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to resume queue',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to resume queue', [
                'queue' => $queueName,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to resume queue',
            ], 500);
        }
    }
} 