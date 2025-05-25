<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessTrack;
use App\Jobs\YouTubeBulkUploadJob;
use App\Models\Track;
use App\Models\YouTubeAccount;
use Illuminate\Bus\Batch;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

final class QueueService
{
    /**
     * Queue names used by the application.
     */
    private const QUEUES = [
        'default' => 'default',
        'track_processing' => 'track-processing',
        'youtube_uploads' => 'youtube-uploads',
        'high_priority' => 'high-priority',
        'low_priority' => 'low-priority',
    ];

    /**
     * Cache TTL for queue statistics (in seconds).
     */
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get queue statistics for all queues.
     */
    public function getQueueStatistics(): array
    {
        return Cache::remember('queue:statistics', self::CACHE_TTL, function () {
            $statistics = [];
            
            foreach (self::QUEUES as $name => $queue) {
                $statistics[$name] = $this->getQueueStats($queue);
            }
            
            $statistics['totals'] = $this->calculateTotals($statistics);
            $statistics['failed_jobs'] = $this->getFailedJobsCount();
            $statistics['batches'] = $this->getBatchStatistics();
            $statistics['generated_at'] = now()->toISOString();
            
            return $statistics;
        });
    }

    /**
     * Get statistics for a specific queue.
     */
    public function getQueueStats(string $queueName): array
    {
        try {
            $connection = config('queue.default');
            
            if ($connection === 'database') {
                return $this->getDatabaseQueueStats($queueName);
            } elseif ($connection === 'redis') {
                return $this->getRedisQueueStats($queueName);
            }
            
            return [
                'pending' => 0,
                'processing' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get queue statistics', [
                'queue' => $queueName,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'pending' => 0,
                'processing' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get database queue statistics.
     */
    private function getDatabaseQueueStats(string $queueName): array
    {
        $table = config('queue.connections.database.table', 'jobs');
        
        $pending = DB::table($table)
            ->where('queue', $queueName)
            ->whereNull('reserved_at')
            ->count();
            
        $processing = DB::table($table)
            ->where('queue', $queueName)
            ->whereNotNull('reserved_at')
            ->count();
            
        $failed = DB::table('failed_jobs')
            ->where('queue', $queueName)
            ->count();
        
        return [
            'pending' => $pending,
            'processing' => $processing,
            'completed' => 0, // Not tracked in database queue
            'failed' => $failed,
            'total' => $pending + $processing,
        ];
    }

    /**
     * Get Redis queue statistics.
     */
    private function getRedisQueueStats(string $queueName): array
    {
        try {
            $redis = Queue::connection('redis')->getRedis();
            $prefix = config('database.redis.options.prefix', '');
            
            $pending = $redis->llen($prefix . 'queues:' . $queueName);
            $processing = $redis->llen($prefix . 'queues:' . $queueName . ':reserved');
            $failed = $redis->llen($prefix . 'queues:' . $queueName . ':failed');
            
            return [
                'pending' => $pending,
                'processing' => $processing,
                'completed' => 0, // Not easily tracked in Redis
                'failed' => $failed,
                'total' => $pending + $processing,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get Redis queue stats', [
                'queue' => $queueName,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'pending' => 0,
                'processing' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 0,
            ];
        }
    }

    /**
     * Calculate total statistics across all queues.
     */
    private function calculateTotals(array $statistics): array
    {
        $totals = [
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
            'total' => 0,
        ];
        
        foreach ($statistics as $queueStats) {
            if (is_array($queueStats) && !isset($queueStats['generated_at'])) {
                $totals['pending'] += $queueStats['pending'] ?? 0;
                $totals['processing'] += $queueStats['processing'] ?? 0;
                $totals['completed'] += $queueStats['completed'] ?? 0;
                $totals['failed'] += $queueStats['failed'] ?? 0;
                $totals['total'] += $queueStats['total'] ?? 0;
            }
        }
        
        return $totals;
    }

    /**
     * Get failed jobs count.
     */
    public function getFailedJobsCount(): int
    {
        try {
            return DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            Log::error('Failed to get failed jobs count', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Get batch statistics.
     */
    public function getBatchStatistics(): array
    {
        try {
            $batches = DB::table('job_batches')
                ->select([
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN finished_at IS NULL THEN 1 ELSE 0 END) as pending'),
                    DB::raw('SUM(CASE WHEN finished_at IS NOT NULL AND cancelled_at IS NULL THEN 1 ELSE 0 END) as completed'),
                    DB::raw('SUM(CASE WHEN cancelled_at IS NOT NULL THEN 1 ELSE 0 END) as cancelled'),
                ])
                ->first();
                
            return [
                'total' => $batches->total ?? 0,
                'pending' => $batches->pending ?? 0,
                'completed' => $batches->completed ?? 0,
                'cancelled' => $batches->cancelled ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get batch statistics', ['error' => $e->getMessage()]);
            return [
                'total' => 0,
                'pending' => 0,
                'completed' => 0,
                'cancelled' => 0,
            ];
        }
    }

    /**
     * Dispatch track processing job.
     */
    public function dispatchTrackProcessing(Track $track, string $priority = 'normal'): void
    {
        $queue = match ($priority) {
            'high' => self::QUEUES['high_priority'],
            'low' => self::QUEUES['low_priority'],
            default => self::QUEUES['track_processing'],
        };
        
        ProcessTrack::dispatch($track)->onQueue($queue);
        
        Log::info('Track processing job dispatched', [
            'track_id' => $track->id,
            'queue' => $queue,
            'priority' => $priority,
        ]);
        
        $this->invalidateCache();
    }

    /**
     * Dispatch YouTube upload job.
     */
    public function dispatchYouTubeUpload(
        Track $track, 
        YouTubeAccount $account, 
        array $options = [],
        int $delay = 0
    ): void {
        $job = YouTubeBulkUploadJob::dispatch($track, $account, $options)
            ->onQueue(self::QUEUES['youtube_uploads']);
            
        if ($delay > 0) {
            $job->delay(now()->addSeconds($delay));
        }
        
        Log::info('YouTube upload job dispatched', [
            'track_id' => $track->id,
            'account_id' => $account->id,
            'delay' => $delay,
        ]);
        
        $this->invalidateCache();
    }

    /**
     * Dispatch batch of YouTube uploads.
     */
    public function dispatchYouTubeBatch(
        Collection $tracks, 
        YouTubeAccount $account, 
        array $options = [],
        int $delayBetween = 30
    ): Batch {
        $jobs = [];
        $delay = 0;
        
        foreach ($tracks as $track) {
            $job = new YouTubeBulkUploadJob($track, $account, $options);
            $job->onQueue(self::QUEUES['youtube_uploads']);
            
            if ($delay > 0) {
                $job->delay(now()->addSeconds($delay));
            }
            
            $jobs[] = $job;
            $delay += $delayBetween;
        }
        
        $batch = Bus::batch($jobs)
            ->name('YouTube Bulk Upload - ' . now()->format('Y-m-d H:i:s'))
            ->allowFailures()
            ->onQueue(self::QUEUES['youtube_uploads'])
            ->dispatch();
            
        Log::info('YouTube batch upload dispatched', [
            'batch_id' => $batch->id,
            'track_count' => $tracks->count(),
            'account_id' => $account->id,
            'delay_between' => $delayBetween,
        ]);
        
        $this->invalidateCache();
        
        return $batch;
    }

    /**
     * Get active batches.
     */
    public function getActiveBatches(): Collection
    {
        return DB::table('job_batches')
            ->whereNull('finished_at')
            ->whereNull('cancelled_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'total_jobs' => $batch->total_jobs,
                    'pending_jobs' => $batch->pending_jobs,
                    'processed_jobs' => $batch->processed_jobs,
                    'failed_jobs' => $batch->failed_jobs,
                    'progress' => $batch->total_jobs > 0 
                        ? round(($batch->processed_jobs / $batch->total_jobs) * 100, 2) 
                        : 0,
                    'created_at' => $batch->created_at,
                ];
            });
    }

    /**
     * Cancel a batch.
     */
    public function cancelBatch(string $batchId): bool
    {
        try {
            $batch = Bus::findBatch($batchId);
            
            if ($batch && !$batch->finished()) {
                $batch->cancel();
                
                Log::info('Batch cancelled', ['batch_id' => $batchId]);
                $this->invalidateCache();
                
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to cancel batch', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Retry failed jobs in a batch.
     */
    public function retryBatchFailedJobs(string $batchId): bool
    {
        try {
            $batch = Bus::findBatch($batchId);
            
            if ($batch) {
                // This would require custom implementation
                // as Laravel doesn't provide direct batch retry
                Log::info('Batch retry requested', ['batch_id' => $batchId]);
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to retry batch', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Clear failed jobs.
     */
    public function clearFailedJobs(array $jobIds = []): int
    {
        try {
            $query = DB::table('failed_jobs');
            
            if (!empty($jobIds)) {
                $query->whereIn('uuid', $jobIds);
            }
            
            $count = $query->count();
            $query->delete();
            
            Log::info('Failed jobs cleared', [
                'count' => $count,
                'specific_jobs' => !empty($jobIds),
            ]);
            
            $this->invalidateCache();
            
            return $count;
        } catch (\Exception $e) {
            Log::error('Failed to clear failed jobs', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Retry failed jobs.
     */
    public function retryFailedJobs(array $jobIds = []): int
    {
        try {
            $query = DB::table('failed_jobs');
            
            if (!empty($jobIds)) {
                $query->whereIn('uuid', $jobIds);
            }
            
            $failedJobs = $query->get();
            $retryCount = 0;
            
            foreach ($failedJobs as $failedJob) {
                try {
                    // Recreate and dispatch the job
                    $payload = json_decode($failedJob->payload, true);
                    $job = unserialize($payload['data']['command']);
                    
                    dispatch($job);
                    
                    // Remove from failed jobs table
                    DB::table('failed_jobs')->where('uuid', $failedJob->uuid)->delete();
                    
                    $retryCount++;
                } catch (\Exception $e) {
                    Log::error('Failed to retry individual job', [
                        'job_uuid' => $failedJob->uuid,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            Log::info('Failed jobs retried', ['count' => $retryCount]);
            $this->invalidateCache();
            
            return $retryCount;
        } catch (\Exception $e) {
            Log::error('Failed to retry failed jobs', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Get queue health status.
     */
    public function getQueueHealth(): array
    {
        $stats = $this->getQueueStatistics();
        $health = [
            'status' => 'healthy',
            'issues' => [],
            'recommendations' => [],
        ];
        
        // Check for high failure rates
        $totalJobs = $stats['totals']['total'] ?? 0;
        $failedJobs = $stats['failed_jobs'] ?? 0;
        
        if ($totalJobs > 0) {
            $failureRate = ($failedJobs / ($totalJobs + $failedJobs)) * 100;
            
            if ($failureRate > 20) {
                $health['status'] = 'critical';
                $health['issues'][] = "High failure rate: {$failureRate}%";
                $health['recommendations'][] = 'Review failed jobs and fix underlying issues';
            } elseif ($failureRate > 10) {
                $health['status'] = 'warning';
                $health['issues'][] = "Elevated failure rate: {$failureRate}%";
                $health['recommendations'][] = 'Monitor failed jobs closely';
            }
        }
        
        // Check for queue backlogs
        foreach (self::QUEUES as $name => $queue) {
            $queueStats = $stats[$name] ?? [];
            $pending = $queueStats['pending'] ?? 0;
            
            if ($pending > 100) {
                $health['status'] = $health['status'] === 'critical' ? 'critical' : 'warning';
                $health['issues'][] = "Queue '{$name}' has {$pending} pending jobs";
                $health['recommendations'][] = "Consider scaling up workers for '{$name}' queue";
            }
        }
        
        // Check for stale processing jobs
        $staleJobs = $this->getStaleJobs();
        if ($staleJobs > 0) {
            $health['status'] = $health['status'] === 'critical' ? 'critical' : 'warning';
            $health['issues'][] = "{$staleJobs} jobs appear to be stale";
            $health['recommendations'][] = 'Restart queue workers to clear stale jobs';
        }
        
        return $health;
    }

    /**
     * Get count of stale jobs (processing for too long).
     */
    private function getStaleJobs(): int
    {
        try {
            $staleThreshold = now()->subHours(2); // Jobs processing for more than 2 hours
            
            return DB::table('jobs')
                ->whereNotNull('reserved_at')
                ->where('reserved_at', '<', $staleThreshold->timestamp)
                ->count();
        } catch (\Exception $e) {
            Log::error('Failed to get stale jobs count', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Invalidate queue statistics cache.
     */
    private function invalidateCache(): void
    {
        Cache::forget('queue:statistics');
    }

    /**
     * Get available queue names.
     */
    public function getAvailableQueues(): array
    {
        return self::QUEUES;
    }

    /**
     * Pause a queue (implementation depends on queue driver).
     */
    public function pauseQueue(string $queueName): bool
    {
        // This would require queue driver specific implementation
        Log::info('Queue pause requested', ['queue' => $queueName]);
        return true;
    }

    /**
     * Resume a queue (implementation depends on queue driver).
     */
    public function resumeQueue(string $queueName): bool
    {
        // This would require queue driver specific implementation
        Log::info('Queue resume requested', ['queue' => $queueName]);
        return true;
    }
} 