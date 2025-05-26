<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Throwable;

final class LoggingService
{
    private const PERFORMANCE_THRESHOLD_MS = 1000;
    private const ERROR_RATE_THRESHOLD = 0.1; // 10%
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Log application events with structured context.
     */
    public function logEvent(string $event, array $context = [], string $level = 'info'): void
    {
        $structuredContext = $this->buildStructuredContext($context);
        
        Log::channel('single')->log($level, "Event: {$event}", $structuredContext);
        
        // Store event metrics for monitoring
        $this->recordEventMetric($event, $level);
    }

    /**
     * Log performance metrics for operations.
     */
    public function logPerformance(string $operation, float $duration, array $context = []): void
    {
        $durationMs = round($duration * 1000, 2);
        
        $performanceContext = array_merge($context, [
            'operation' => $operation,
            'duration_ms' => $durationMs,
            'is_slow' => $durationMs > self::PERFORMANCE_THRESHOLD_MS,
            'timestamp' => now()->toISOString(),
        ]);

        $level = $durationMs > self::PERFORMANCE_THRESHOLD_MS ? 'warning' : 'info';
        
        Log::channel('single')->log($level, "Performance: {$operation}", $performanceContext);
        
        // Store performance metrics
        $this->recordPerformanceMetric($operation, $durationMs);
    }

    /**
     * Log errors with comprehensive context.
     */
    public function logError(Throwable $exception, array $context = []): void
    {
        $errorContext = array_merge($context, [
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => now()->toISOString(),
            'request_id' => $this->getRequestId(),
        ]);

        Log::error("Exception: {$exception->getMessage()}", $errorContext);
        
        // Record error metrics
        $this->recordErrorMetric(get_class($exception));
    }

    /**
     * Log database query performance.
     */
    public function logDatabaseQuery(string $query, float $duration, array $bindings = []): void
    {
        $durationMs = round($duration * 1000, 2);
        
        $queryContext = [
            'query' => $query,
            'bindings' => $bindings,
            'duration_ms' => $durationMs,
            'is_slow' => $durationMs > 100, // 100ms threshold for slow queries
            'timestamp' => now()->toISOString(),
        ];

        $level = $durationMs > 100 ? 'warning' : 'debug';
        
        Log::channel('single')->log($level, "Database Query", $queryContext);
    }

    /**
     * Log YouTube API operations.
     */
    public function logYouTubeOperation(string $operation, bool $success, array $context = []): void
    {
        $youtubeContext = array_merge($context, [
            'operation' => $operation,
            'success' => $success,
            'service' => 'youtube',
            'timestamp' => now()->toISOString(),
        ]);

        $level = $success ? 'info' : 'error';
        
        Log::channel('single')->log($level, "YouTube: {$operation}", $youtubeContext);
        
        // Record YouTube operation metrics
        $this->recordYouTubeMetric($operation, $success);
    }

    /**
     * Log track processing operations.
     */
    public function logTrackProcessing(int $trackId, string $status, array $context = []): void
    {
        $trackContext = array_merge($context, [
            'track_id' => $trackId,
            'status' => $status,
            'service' => 'track_processing',
            'timestamp' => now()->toISOString(),
        ]);

        Log::info("Track Processing: {$status}", $trackContext);
        
        // Record track processing metrics
        $this->recordTrackProcessingMetric($status);
    }

    /**
     * Log queue operations.
     */
    public function logQueueOperation(string $job, string $queue, string $status, array $context = []): void
    {
        $queueContext = array_merge($context, [
            'job' => $job,
            'queue' => $queue,
            'status' => $status,
            'service' => 'queue',
            'timestamp' => now()->toISOString(),
        ]);

        $level = $status === 'failed' ? 'error' : 'info';
        
        Log::channel('single')->log($level, "Queue: {$job}", $queueContext);
        
        // Record queue metrics
        $this->recordQueueMetric($queue, $status);
    }

    /**
     * Get system health metrics.
     */
    public function getSystemHealth(): array
    {
        return [
            'database' => $this->getDatabaseHealth(),
            'redis' => $this->getRedisHealth(),
            'queue' => $this->getQueueHealth(),
            'storage' => $this->getStorageHealth(),
            'performance' => $this->getPerformanceMetrics(),
            'errors' => $this->getErrorMetrics(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get performance metrics summary.
     */
    public function getPerformanceMetrics(): array
    {
        $cacheKey = 'performance_metrics';
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return [
                'slow_operations' => $this->getSlowOperations(),
                'average_response_time' => $this->getAverageResponseTime(),
                'database_performance' => $this->getDatabasePerformance(),
                'memory_usage' => $this->getMemoryUsage(),
            ];
        });
    }

    /**
     * Get error rate metrics.
     */
    public function getErrorMetrics(): array
    {
        $cacheKey = 'error_metrics';
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return [
                'error_rate' => $this->calculateErrorRate(),
                'top_errors' => $this->getTopErrors(),
                'recent_errors' => $this->getRecentErrors(),
                'error_trends' => $this->getErrorTrends(),
            ];
        });
    }

    /**
     * Monitor system health and alert on issues.
     */
    public function monitorSystemHealth(): array
    {
        $health = $this->getSystemHealth();
        $alerts = [];

        // Check database health
        if (!$health['database']['connected']) {
            $alerts[] = [
                'type' => 'critical',
                'message' => 'Database connection failed',
                'service' => 'database',
            ];
        }

        // Check Redis health
        if (!$health['redis']['connected']) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Redis connection failed',
                'service' => 'redis',
            ];
        }

        // Check queue health
        if ($health['queue']['failed_jobs'] > 50) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "High number of failed jobs: {$health['queue']['failed_jobs']}",
                'service' => 'queue',
            ];
        }

        // Check error rate
        if ($health['errors']['error_rate'] > self::ERROR_RATE_THRESHOLD) {
            $alerts[] = [
                'type' => 'critical',
                'message' => "High error rate: {$health['errors']['error_rate']}",
                'service' => 'application',
            ];
        }

        // Log alerts
        foreach ($alerts as $alert) {
            $this->logEvent('system_alert', $alert, $alert['type'] === 'critical' ? 'critical' : 'warning');
        }

        return [
            'health' => $health,
            'alerts' => $alerts,
            'status' => empty($alerts) ? 'healthy' : 'issues_detected',
        ];
    }

    /**
     * Build structured context for logging.
     */
    private function buildStructuredContext(array $context): array
    {
        return array_merge($context, [
            'request_id' => $this->getRequestId(),
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get or generate request ID for tracing.
     */
    private function getRequestId(): string
    {
        return request()->header('X-Request-ID') ?? Str::uuid()->toString();
    }

    /**
     * Record event metrics for monitoring.
     */
    private function recordEventMetric(string $event, string $level): void
    {
        $key = "metrics:events:{$event}:{$level}";
        Cache::increment($key, 1);
        Cache::expire($key, 3600); // 1 hour TTL
    }

    /**
     * Record performance metrics.
     */
    private function recordPerformanceMetric(string $operation, float $duration): void
    {
        $key = "metrics:performance:{$operation}";
        $metrics = Cache::get($key, []);
        
        $metrics[] = [
            'duration' => $duration,
            'timestamp' => now()->timestamp,
        ];
        
        // Keep only last 100 measurements
        if (count($metrics) > 100) {
            $metrics = array_slice($metrics, -100);
        }
        
        Cache::put($key, $metrics, 3600);
    }

    /**
     * Record error metrics.
     */
    private function recordErrorMetric(string $exceptionClass): void
    {
        $key = "metrics:errors:{$exceptionClass}";
        Cache::increment($key, 1);
        Cache::expire($key, 3600);
    }

    /**
     * Record YouTube operation metrics.
     */
    private function recordYouTubeMetric(string $operation, bool $success): void
    {
        $status = $success ? 'success' : 'failure';
        $key = "metrics:youtube:{$operation}:{$status}";
        Cache::increment($key, 1);
        Cache::expire($key, 3600);
    }

    /**
     * Record track processing metrics.
     */
    private function recordTrackProcessingMetric(string $status): void
    {
        $key = "metrics:tracks:{$status}";
        Cache::increment($key, 1);
        Cache::expire($key, 3600);
    }

    /**
     * Record queue metrics.
     */
    private function recordQueueMetric(string $queue, string $status): void
    {
        $key = "metrics:queue:{$queue}:{$status}";
        Cache::increment($key, 1);
        Cache::expire($key, 3600);
    }

    /**
     * Get database health status.
     */
    private function getDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $duration = (microtime(true) - $start) * 1000;
            
            return [
                'connected' => true,
                'response_time_ms' => round($duration, 2),
                'connection_count' => $this->getDatabaseConnectionCount(),
            ];
        } catch (Throwable $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get Redis health status.
     */
    private function getRedisHealth(): array
    {
        try {
            $start = microtime(true);
            Redis::ping();
            $duration = (microtime(true) - $start) * 1000;
            
            return [
                'connected' => true,
                'response_time_ms' => round($duration, 2),
                'memory_usage' => Redis::info('memory')['used_memory_human'] ?? 'unknown',
            ];
        } catch (Throwable $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get queue health status.
     */
    private function getQueueHealth(): array
    {
        try {
            return [
                'pending_jobs' => DB::table('jobs')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
                'processed_jobs' => Cache::get('metrics:queue:processed', 0),
            ];
        } catch (Throwable $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get storage health status.
     */
    private function getStorageHealth(): array
    {
        $storagePath = storage_path();
        
        return [
            'disk_free_space' => disk_free_space($storagePath),
            'disk_total_space' => disk_total_space($storagePath),
            'disk_usage_percent' => round((1 - disk_free_space($storagePath) / disk_total_space($storagePath)) * 100, 2),
        ];
    }

    /**
     * Get slow operations.
     */
    private function getSlowOperations(): array
    {
        // Implementation would analyze performance metrics
        return [];
    }

    /**
     * Get average response time.
     */
    private function getAverageResponseTime(): float
    {
        // Implementation would calculate from performance metrics
        return 0.0;
    }

    /**
     * Get database performance metrics.
     */
    private function getDatabasePerformance(): array
    {
        return [
            'slow_queries' => 0, // Would be implemented with query logging
            'connection_count' => $this->getDatabaseConnectionCount(),
        ];
    }

    /**
     * Get memory usage.
     */
    private function getMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
        ];
    }

    /**
     * Calculate error rate.
     */
    private function calculateErrorRate(): float
    {
        // Implementation would calculate from error metrics
        return 0.0;
    }

    /**
     * Get top errors.
     */
    private function getTopErrors(): array
    {
        // Implementation would analyze error metrics
        return [];
    }

    /**
     * Get recent errors.
     */
    private function getRecentErrors(): array
    {
        // Implementation would get recent error logs
        return [];
    }

    /**
     * Get error trends.
     */
    private function getErrorTrends(): array
    {
        // Implementation would analyze error trends over time
        return [];
    }

    /**
     * Get database connection count.
     */
    private function getDatabaseConnectionCount(): int
    {
        try {
            $result = DB::select("PRAGMA database_list");
            return count($result);
        } catch (Throwable $e) {
            return 0;
        }
    }
} 