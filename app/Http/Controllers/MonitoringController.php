<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

final class MonitoringController extends Controller
{
    public function __construct(
        private readonly LoggingService $loggingService
    ) {}

    /**
     * Display the monitoring dashboard.
     */
    public function index(): View
    {
        $systemHealth = $this->loggingService->getSystemHealth();
        $performanceMetrics = $this->loggingService->getPerformanceMetrics();
        $errorMetrics = $this->loggingService->getErrorMetrics();

        return view('monitoring.index', compact(
            'systemHealth',
            'performanceMetrics',
            'errorMetrics'
        ));
    }

    /**
     * Get system health status as JSON.
     */
    public function health(): JsonResponse
    {
        $health = $this->loggingService->monitorSystemHealth();
        
        return response()->json([
            'success' => true,
            'data' => $health,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get performance metrics as JSON.
     */
    public function performance(): JsonResponse
    {
        $metrics = $this->loggingService->getPerformanceMetrics();
        
        return response()->json([
            'success' => true,
            'data' => $metrics,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get error metrics as JSON.
     */
    public function errors(): JsonResponse
    {
        $metrics = $this->loggingService->getErrorMetrics();
        
        return response()->json([
            'success' => true,
            'data' => $metrics,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get recent log entries.
     */
    public function logs(Request $request): JsonResponse
    {
        $request->validate([
            'level' => 'nullable|in:debug,info,warning,error,critical',
            'limit' => 'nullable|integer|min:1|max:1000',
            'service' => 'nullable|string|max:50',
        ]);

        $level = $request->get('level');
        $limit = $request->get('limit', 100);
        $service = $request->get('service');

        $logs = $this->getRecentLogs($level, $limit, $service);

        return response()->json([
            'success' => true,
            'data' => $logs,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get system metrics summary.
     */
    public function metrics(): JsonResponse
    {
        $metrics = [
            'system' => $this->getSystemMetrics(),
            'application' => $this->getApplicationMetrics(),
            'performance' => $this->getPerformanceOverview(),
            'errors' => $this->getErrorOverview(),
        ];

        return response()->json([
            'success' => true,
            'data' => $metrics,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get real-time statistics.
     */
    public function realtime(): JsonResponse
    {
        $stats = [
            'active_connections' => $this->getActiveConnections(),
            'memory_usage' => $this->getCurrentMemoryUsage(),
            'cpu_usage' => $this->getCpuUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'queue_status' => $this->getQueueStatus(),
            'cache_status' => $this->getCacheStatus(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Clear cache and reset metrics.
     */
    public function clearCache(): JsonResponse
    {
        try {
            // Clear application cache
            Cache::flush();
            
            // Log the cache clear event
            $this->loggingService->logEvent('cache_cleared', [
                'action' => 'manual_clear',
                'user_ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            $this->loggingService->logError($e, ['action' => 'clear_cache']);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export system health report.
     */
    public function exportReport(): JsonResponse
    {
        try {
            $report = [
                'generated_at' => now()->toISOString(),
                'system_health' => $this->loggingService->getSystemHealth(),
                'performance_metrics' => $this->loggingService->getPerformanceMetrics(),
                'error_metrics' => $this->loggingService->getErrorMetrics(),
                'recent_logs' => $this->getRecentLogs(null, 50),
                'system_info' => $this->getSystemInfo(),
            ];

            return response()->json([
                'success' => true,
                'data' => $report,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            $this->loggingService->logError($e, ['action' => 'export_report']);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test logging functionality.
     */
    public function testLogging(): JsonResponse
    {
        try {
            // Test different log levels
            $this->loggingService->logEvent('test_event', ['test' => true], 'info');
            $this->loggingService->logPerformance('test_operation', 0.5, ['test' => true]);
            
            // Test error logging
            try {
                throw new \Exception('Test exception for logging');
            } catch (\Exception $e) {
                $this->loggingService->logError($e, ['test' => true]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Logging test completed successfully',
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logging test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent log entries from log files.
     */
    private function getRecentLogs(?string $level = null, int $limit = 100, ?string $service = null): array
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (!File::exists($logFile)) {
            return [];
        }

        $logs = [];
        $handle = fopen($logFile, 'r');
        
        if ($handle) {
            // Read from the end of the file
            fseek($handle, -8192, SEEK_END); // Read last 8KB
            $content = fread($handle, 8192);
            fclose($handle);
            
            $lines = explode("\n", $content);
            $lines = array_reverse(array_filter($lines));
            
            foreach ($lines as $line) {
                if (count($logs) >= $limit) {
                    break;
                }
                
                $logEntry = $this->parseLogLine($line);
                if ($logEntry && $this->matchesFilters($logEntry, $level, $service)) {
                    $logs[] = $logEntry;
                }
            }
        }

        return array_slice($logs, 0, $limit);
    }

    /**
     * Parse a log line into structured data.
     */
    private function parseLogLine(string $line): ?array
    {
        // Basic log line parsing - could be enhanced for more complex formats
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.+)/', $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'message' => $matches[3],
                'raw' => $line,
            ];
        }
        
        return null;
    }

    /**
     * Check if log entry matches filters.
     */
    private function matchesFilters(array $logEntry, ?string $level, ?string $service): bool
    {
        if ($level && $logEntry['level'] !== $level) {
            return false;
        }
        
        if ($service && !str_contains($logEntry['message'], $service)) {
            return false;
        }
        
        return true;
    }

    /**
     * Get system metrics.
     */
    private function getSystemMetrics(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_time' => now()->toISOString(),
            'timezone' => config('app.timezone'),
            'environment' => app()->environment(),
        ];
    }

    /**
     * Get application metrics.
     */
    private function getApplicationMetrics(): array
    {
        return [
            'total_tracks' => Cache::remember('metrics:tracks:total', 300, fn() => \App\Models\Track::count()),
            'total_genres' => Cache::remember('metrics:genres:total', 300, fn() => \App\Models\Genre::count()),
            'pending_jobs' => Cache::remember('metrics:jobs:pending', 60, fn() => \DB::table('jobs')->count()),
            'failed_jobs' => Cache::remember('metrics:jobs:failed', 60, fn() => \DB::table('failed_jobs')->count()),
        ];
    }

    /**
     * Get performance overview.
     */
    private function getPerformanceOverview(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_limit' => ini_get('memory_limit'),
            'execution_time' => microtime(true) - LARAVEL_START,
        ];
    }

    /**
     * Get error overview.
     */
    private function getErrorOverview(): array
    {
        return [
            'recent_errors' => Cache::get('metrics:errors:recent', 0),
            'error_rate' => Cache::get('metrics:errors:rate', 0.0),
            'last_error' => Cache::get('metrics:errors:last_timestamp'),
        ];
    }

    /**
     * Get active connections count.
     */
    private function getActiveConnections(): int
    {
        // This would be implemented based on your web server
        return 0;
    }

    /**
     * Get current memory usage.
     */
    private function getCurrentMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'percentage' => round((memory_get_usage(true) / $this->parseMemoryLimit()) * 100, 2),
        ];
    }

    /**
     * Get CPU usage (simplified).
     */
    private function getCpuUsage(): float
    {
        // This would require system-specific implementation
        return 0.0;
    }

    /**
     * Get disk usage.
     */
    private function getDiskUsage(): array
    {
        $path = storage_path();
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'percentage' => round(($used / $total) * 100, 2),
        ];
    }

    /**
     * Get queue status.
     */
    private function getQueueStatus(): array
    {
        return [
            'pending' => \DB::table('jobs')->count(),
            'failed' => \DB::table('failed_jobs')->count(),
            'processed' => Cache::get('metrics:queue:processed', 0),
        ];
    }

    /**
     * Get cache status.
     */
    private function getCacheStatus(): array
    {
        try {
            $start = microtime(true);
            Cache::put('health_check', 'ok', 1);
            $writeTime = (microtime(true) - $start) * 1000;
            
            $start = microtime(true);
            $value = Cache::get('health_check');
            $readTime = (microtime(true) - $start) * 1000;
            
            return [
                'status' => $value === 'ok' ? 'healthy' : 'unhealthy',
                'write_time_ms' => round($writeTime, 2),
                'read_time_ms' => round($readTime, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get system information.
     */
    private function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'operating_system' => PHP_OS,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];
    }

    /**
     * Parse memory limit string to bytes.
     */
    private function parseMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);
        
        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
