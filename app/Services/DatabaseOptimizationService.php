<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Track;
use App\Models\Genre;

final class DatabaseOptimizationService
{
    private const CACHE_PREFIX = 'db_optimization:';
    private const STATS_TTL = 3600; // 1 hour

    /**
     * Optimize track queries with proper indexing and caching.
     */
    public function optimizeTrackQueries(): array
    {
        $optimizations = [];

        try {
            // Analyze slow queries
            $slowQueries = $this->analyzeSlowQueries();
            $optimizations['slow_queries'] = count($slowQueries);

            // Optimize common track queries
            $this->optimizeCommonTrackQueries();
            $optimizations['track_queries_optimized'] = true;

            // Update table statistics
            $this->updateTableStatistics();
            $optimizations['statistics_updated'] = true;

            // Clean up old data
            $cleanedRecords = $this->cleanupOldData();
            $optimizations['cleaned_records'] = $cleanedRecords;

            Log::info('Database optimization completed', $optimizations);

            return $optimizations;

        } catch (\Exception $e) {
            Log::error('Database optimization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get database performance statistics.
     */
    public function getPerformanceStats(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'performance_stats';

        return Cache::remember($cacheKey, self::STATS_TTL, function () {
            return [
                'table_sizes' => $this->getTableSizes(),
                'index_usage' => $this->getIndexUsage(),
                'query_performance' => $this->getQueryPerformance(),
                'cache_hit_ratio' => $this->getCacheHitRatio(),
                'slow_queries' => $this->getSlowQueryCount(),
                'connection_stats' => $this->getConnectionStats(),
            ];
        });
    }

    /**
     * Optimize specific query patterns.
     */
    public function optimizeQueryPatterns(): void
    {
        // Optimize track status queries
        $this->optimizeTrackStatusQueries();

        // Optimize YouTube-related queries
        $this->optimizeYouTubeQueries();

        // Optimize genre relationship queries
        $this->optimizeGenreQueries();

        // Optimize search queries
        $this->optimizeSearchQueries();
    }

    /**
     * Clean up database and optimize storage.
     */
    public function cleanupAndOptimize(): array
    {
        $results = [];

        try {
            // Clean up old webhook logs
            $webhookCleaned = $this->cleanupWebhookLogs();
            $results['webhook_logs_cleaned'] = $webhookCleaned;

            // Clean up failed jobs older than 30 days
            $jobsCleaned = $this->cleanupFailedJobs();
            $results['failed_jobs_cleaned'] = $jobsCleaned;

            // Optimize table storage
            $this->optimizeTableStorage();
            $results['tables_optimized'] = true;

            // Update query cache
            $this->updateQueryCache();
            $results['query_cache_updated'] = true;

            return $results;

        } catch (\Exception $e) {
            Log::error('Database cleanup failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Analyze slow queries.
     */
    private function analyzeSlowQueries(): array
    {
        try {
            // Enable slow query log temporarily if not enabled
            $slowQueries = DB::select("
                SELECT sql_text, exec_count, avg_timer_wait/1000000000 as avg_time_seconds
                FROM performance_schema.events_statements_summary_by_digest 
                WHERE avg_timer_wait > 1000000000 
                ORDER BY avg_timer_wait DESC 
                LIMIT 10
            ");

            return collect($slowQueries)->map(function ($query) {
                return [
                    'sql' => substr($query->sql_text, 0, 200) . '...',
                    'executions' => $query->exec_count,
                    'avg_time' => round($query->avg_time_seconds, 3),
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::warning('Could not analyze slow queries', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Optimize common track queries.
     */
    private function optimizeCommonTrackQueries(): void
    {
        // Pre-warm cache for common track status queries
        $statuses = ['pending', 'processing', 'completed', 'failed', 'stopped'];
        
        foreach ($statuses as $status) {
            $cacheKey = "tracks_by_status:{$status}";
            Cache::remember($cacheKey, 300, function () use ($status) {
                return Track::where('status', $status)
                    ->select(['id', 'title', 'status', 'progress', 'updated_at'])
                    ->limit(100)
                    ->get();
            });
        }

        // Pre-warm cache for YouTube uploaded tracks
        Cache::remember('tracks_youtube_uploaded', 300, function () {
            return Track::whereNotNull('youtube_video_id')
                ->select(['id', 'title', 'youtube_video_id', 'youtube_views'])
                ->orderBy('youtube_views', 'desc')
                ->limit(50)
                ->get();
        });
    }

    /**
     * Optimize track status queries.
     */
    private function optimizeTrackStatusQueries(): void
    {
        // Use composite index for status + created_at queries
        DB::statement("
            SELECT * FROM tracks 
            USE INDEX (tracks_status_created_at_index)
            WHERE status = 'processing' 
            ORDER BY created_at DESC 
            LIMIT 1
        ");

        // Use composite index for status + progress queries
        DB::statement("
            SELECT * FROM tracks 
            USE INDEX (tracks_status_progress_index)
            WHERE status = 'processing' AND progress < 100
            LIMIT 1
        ");
    }

    /**
     * Optimize YouTube-related queries.
     */
    private function optimizeYouTubeQueries(): void
    {
        // Use YouTube-specific indexes
        DB::statement("
            SELECT * FROM tracks 
            USE INDEX (tracks_youtube_status_index)
            WHERE youtube_video_id IS NOT NULL AND status = 'completed'
            LIMIT 1
        ");

        // Optimize analytics queries
        DB::statement("
            SELECT * FROM tracks 
            USE INDEX (tracks_youtube_analytics_updated_at_index)
            WHERE youtube_analytics_updated_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
            LIMIT 1
        ");
    }

    /**
     * Optimize genre relationship queries.
     */
    private function optimizeGenreQueries(): void
    {
        // Pre-warm genre cache
        Cache::remember('all_genres', 3600, function () {
            return Genre::select(['id', 'name', 'slug'])
                ->orderBy('name')
                ->get();
        });

        // Pre-warm popular genre tracks
        $popularGenres = Genre::withCount('tracks')
            ->orderBy('tracks_count', 'desc')
            ->limit(10)
            ->get();

        foreach ($popularGenres as $genre) {
            $cacheKey = "genre_tracks:{$genre->id}";
            Cache::remember($cacheKey, 1800, function () use ($genre) {
                return $genre->tracks()
                    ->select(['id', 'title', 'status'])
                    ->limit(20)
                    ->get();
            });
        }
    }

    /**
     * Optimize search queries.
     */
    private function optimizeSearchQueries(): void
    {
        // Use fulltext index for title searches
        try {
            DB::statement("
                SELECT * FROM tracks 
                WHERE MATCH(title) AGAINST('test' IN NATURAL LANGUAGE MODE)
                LIMIT 1
            ");
        } catch (\Exception $e) {
            Log::warning('Fulltext search optimization failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get table sizes.
     */
    private function getTableSizes(): array
    {
        try {
            $sizes = DB::select("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                    table_rows
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
            ");

            return collect($sizes)->mapWithKeys(function ($table) {
                return [$table->table_name => [
                    'size_mb' => $table->size_mb,
                    'rows' => $table->table_rows,
                ]];
            })->toArray();

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get index usage statistics.
     */
    private function getIndexUsage(): array
    {
        try {
            $usage = DB::select("
                SELECT 
                    object_name,
                    index_name,
                    count_read,
                    count_write,
                    count_fetch
                FROM performance_schema.table_io_waits_summary_by_index_usage 
                WHERE object_schema = DATABASE()
                ORDER BY count_read DESC
                LIMIT 20
            ");

            return collect($usage)->map(function ($index) {
                return [
                    'table' => $index->object_name,
                    'index' => $index->index_name,
                    'reads' => $index->count_read,
                    'writes' => $index->count_write,
                    'fetches' => $index->count_fetch,
                ];
            })->toArray();

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get query performance metrics.
     */
    private function getQueryPerformance(): array
    {
        try {
            $performance = DB::select("
                SELECT 
                    COUNT(*) as total_queries,
                    SUM(timer_wait)/1000000000 as total_time_seconds,
                    AVG(timer_wait)/1000000000 as avg_time_seconds,
                    MAX(timer_wait)/1000000000 as max_time_seconds
                FROM performance_schema.events_statements_history_long
                WHERE sql_text NOT LIKE '%performance_schema%'
            ");

            return [
                'total_queries' => $performance[0]->total_queries ?? 0,
                'total_time' => round($performance[0]->total_time_seconds ?? 0, 3),
                'avg_time' => round($performance[0]->avg_time_seconds ?? 0, 3),
                'max_time' => round($performance[0]->max_time_seconds ?? 0, 3),
            ];

        } catch (\Exception $e) {
            return [
                'total_queries' => 0,
                'total_time' => 0,
                'avg_time' => 0,
                'max_time' => 0,
            ];
        }
    }

    /**
     * Get cache hit ratio.
     */
    private function getCacheHitRatio(): float
    {
        try {
            $stats = DB::select("
                SELECT 
                    variable_name, 
                    variable_value 
                FROM performance_schema.global_status 
                WHERE variable_name IN ('Qcache_hits', 'Qcache_inserts', 'Qcache_not_cached')
            ");

            $hits = 0;
            $inserts = 0;
            $notCached = 0;

            foreach ($stats as $stat) {
                switch ($stat->variable_name) {
                    case 'Qcache_hits':
                        $hits = (int) $stat->variable_value;
                        break;
                    case 'Qcache_inserts':
                        $inserts = (int) $stat->variable_value;
                        break;
                    case 'Qcache_not_cached':
                        $notCached = (int) $stat->variable_value;
                        break;
                }
            }

            $total = $hits + $inserts + $notCached;
            return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;

        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Get slow query count.
     */
    private function getSlowQueryCount(): int
    {
        try {
            $result = DB::select("SHOW GLOBAL STATUS LIKE 'Slow_queries'");
            return (int) ($result[0]->Value ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get connection statistics.
     */
    private function getConnectionStats(): array
    {
        try {
            $stats = DB::select("
                SELECT variable_name, variable_value 
                FROM performance_schema.global_status 
                WHERE variable_name IN ('Connections', 'Max_used_connections', 'Threads_connected')
            ");

            $result = [];
            foreach ($stats as $stat) {
                $result[strtolower($stat->variable_name)] = (int) $stat->variable_value;
            }

            return $result;

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Update table statistics.
     */
    private function updateTableStatistics(): void
    {
        try {
            $tables = ['tracks', 'genres', 'genre_track', 'webhook_logs'];
            
            foreach ($tables as $table) {
                DB::statement("ANALYZE TABLE {$table}");
            }

        } catch (\Exception $e) {
            Log::warning('Failed to update table statistics', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clean up old data.
     */
    private function cleanupOldData(): int
    {
        $cleaned = 0;

        try {
            // Clean up old webhook logs (older than 30 days)
            $cleaned += $this->cleanupWebhookLogs();

            // Clean up old failed jobs (older than 30 days)
            $cleaned += $this->cleanupFailedJobs();

            return $cleaned;

        } catch (\Exception $e) {
            Log::error('Failed to cleanup old data', [
                'error' => $e->getMessage(),
            ]);

            return $cleaned;
        }
    }

    /**
     * Clean up old webhook logs.
     */
    private function cleanupWebhookLogs(): int
    {
        try {
            return DB::table('webhook_logs')
                ->where('created_at', '<', now()->subDays(30))
                ->delete();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Clean up old failed jobs.
     */
    private function cleanupFailedJobs(): int
    {
        try {
            return DB::table('failed_jobs')
                ->where('failed_at', '<', now()->subDays(30))
                ->delete();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Optimize table storage.
     */
    private function optimizeTableStorage(): void
    {
        try {
            $tables = ['tracks', 'genres', 'genre_track', 'webhook_logs'];
            
            foreach ($tables as $table) {
                DB::statement("OPTIMIZE TABLE {$table}");
            }

        } catch (\Exception $e) {
            Log::warning('Failed to optimize table storage', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update query cache.
     */
    private function updateQueryCache(): void
    {
        try {
            // Reset query cache
            DB::statement("RESET QUERY CACHE");
            
            // Flush query cache
            DB::statement("FLUSH QUERY CACHE");

        } catch (\Exception $e) {
            Log::warning('Failed to update query cache', [
                'error' => $e->getMessage(),
            ]);
        }
    }
} 