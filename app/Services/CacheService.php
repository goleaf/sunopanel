<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Track;
use App\Models\Genre;
use App\Models\Setting;
use App\Models\YouTubeAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

final class CacheService
{
    /**
     * Cache TTL configurations (in seconds).
     */
    private const CACHE_TTL = [
        'tracks_list' => 300,        // 5 minutes
        'track_detail' => 600,       // 10 minutes
        'track_status' => 60,        // 1 minute
        'genres' => 3600,            // 1 hour
        'settings' => 1800,          // 30 minutes
        'youtube_account' => 900,    // 15 minutes
        'statistics' => 300,         // 5 minutes
        'dashboard' => 180,          // 3 minutes
        'api_response' => 120,       // 2 minutes
    ];

    /**
     * Cache key prefixes.
     */
    private const CACHE_KEYS = [
        'tracks_list' => 'tracks:list',
        'track_detail' => 'track:detail',
        'track_status' => 'track:status',
        'genres' => 'genres:all',
        'settings' => 'settings:all',
        'youtube_account' => 'youtube:account',
        'statistics' => 'stats',
        'dashboard' => 'dashboard',
        'api_response' => 'api:response',
    ];

    /**
     * Get cached tracks list with filters.
     */
    public function getTracksList(array $filters = [], int $page = 1, int $limit = 20): ?array
    {
        $cacheKey = $this->generateCacheKey('tracks_list', [
            'filters' => md5(serialize($filters)),
            'page' => $page,
            'limit' => $limit,
        ]);

        return Cache::remember($cacheKey, self::CACHE_TTL['tracks_list'], function () use ($filters, $page, $limit) {
            Log::debug('Cache miss for tracks list', ['filters' => $filters, 'page' => $page]);
            
            $query = Track::with(['genres']);

            // Apply filters
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['genre'])) {
                $query->whereHas('genres', function ($q) use ($filters) {
                    $q->where('slug', $filters['genre']);
                });
            }

            if (!empty($filters['search'])) {
                $query->where('title', 'like', '%' . $filters['search'] . '%');
            }

            // Apply sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            $tracks = $query->paginate($limit, ['*'], 'page', $page);

            return [
                'data' => $tracks->items(),
                'pagination' => [
                    'current_page' => $tracks->currentPage(),
                    'last_page' => $tracks->lastPage(),
                    'per_page' => $tracks->perPage(),
                    'total' => $tracks->total(),
                ],
                'cached_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get cached track detail.
     */
    public function getTrackDetail(int $trackId): ?Track
    {
        $cacheKey = $this->generateCacheKey('track_detail', ['id' => $trackId]);

        return Cache::remember($cacheKey, self::CACHE_TTL['track_detail'], function () use ($trackId) {
            Log::debug('Cache miss for track detail', ['track_id' => $trackId]);
            return Track::with(['genres'])->find($trackId);
        });
    }

    /**
     * Get cached track status.
     */
    public function getTrackStatus(int $trackId): ?array
    {
        $cacheKey = $this->generateCacheKey('track_status', ['id' => $trackId]);

        return Cache::remember($cacheKey, self::CACHE_TTL['track_status'], function () use ($trackId) {
            Log::debug('Cache miss for track status', ['track_id' => $trackId]);
            
            $track = Track::find($trackId);
            if (!$track) {
                return null;
            }

            return [
                'id' => $track->id,
                'status' => $track->status,
                'progress' => $track->progress ?? 0,
                'error_message' => $track->error_message,
                'updated_at' => $track->updated_at,
                'cached_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get cached genres list.
     */
    public function getGenres(): Collection
    {
        $cacheKey = $this->generateCacheKey('genres');

        return Cache::remember($cacheKey, self::CACHE_TTL['genres'], function () {
            Log::debug('Cache miss for genres list');
            return Genre::orderBy('name')->get();
        });
    }

    /**
     * Get cached settings.
     */
    public function getSettings(): array
    {
        $cacheKey = $this->generateCacheKey('settings');

        return Cache::remember($cacheKey, self::CACHE_TTL['settings'], function () {
            Log::debug('Cache miss for settings');
            
            $settings = Setting::all();
            $result = [];
            
            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->value;
            }
            
            return $result;
        });
    }

    /**
     * Get cached YouTube account.
     */
    public function getYouTubeAccount(): ?YouTubeAccount
    {
        $cacheKey = $this->generateCacheKey('youtube_account');

        return Cache::remember($cacheKey, self::CACHE_TTL['youtube_account'], function () {
            Log::debug('Cache miss for YouTube account');
            return YouTubeAccount::where('is_active', true)->first();
        });
    }

    /**
     * Get cached statistics.
     */
    public function getStatistics(): array
    {
        $cacheKey = $this->generateCacheKey('statistics');

        return Cache::remember($cacheKey, self::CACHE_TTL['statistics'], function () {
            Log::debug('Cache miss for statistics');
            
            return [
                'total_tracks' => Track::count(),
                'completed_tracks' => Track::where('status', 'completed')->count(),
                'processing_tracks' => Track::where('status', 'processing')->count(),
                'failed_tracks' => Track::where('status', 'failed')->count(),
                'uploaded_to_youtube' => Track::whereNotNull('youtube_video_id')->count(),
                'pending_upload' => Track::where('status', 'completed')
                    ->whereNull('youtube_video_id')
                    ->count(),
                'total_genres' => Genre::count(),
                'recent_uploads' => Track::whereNotNull('youtube_video_id')
                    ->where('youtube_uploaded_at', '>=', now()->subDays(7))
                    ->count(),
                'cached_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get cached dashboard data.
     */
    public function getDashboardData(): array
    {
        $cacheKey = $this->generateCacheKey('dashboard');

        return Cache::remember($cacheKey, self::CACHE_TTL['dashboard'], function () {
            Log::debug('Cache miss for dashboard data');
            
            return [
                'statistics' => $this->getStatistics(),
                'recent_tracks' => Track::with(['genres'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get(),
                'processing_tracks' => Track::where('status', 'processing')
                    ->orderBy('processing_started_at', 'desc')
                    ->limit(5)
                    ->get(),
                'failed_tracks' => Track::where('status', 'failed')
                    ->orderBy('updated_at', 'desc')
                    ->limit(5)
                    ->get(),
                'cached_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Cache API response.
     */
    public function cacheApiResponse(string $endpoint, array $params, mixed $data): void
    {
        $cacheKey = $this->generateCacheKey('api_response', [
            'endpoint' => $endpoint,
            'params' => md5(serialize($params)),
        ]);

        Cache::put($cacheKey, $data, self::CACHE_TTL['api_response']);
        
        Log::debug('API response cached', [
            'endpoint' => $endpoint,
            'cache_key' => $cacheKey,
            'ttl' => self::CACHE_TTL['api_response'],
        ]);
    }

    /**
     * Get cached API response.
     */
    public function getCachedApiResponse(string $endpoint, array $params): mixed
    {
        $cacheKey = $this->generateCacheKey('api_response', [
            'endpoint' => $endpoint,
            'params' => md5(serialize($params)),
        ]);

        $data = Cache::get($cacheKey);
        
        if ($data) {
            Log::debug('API response cache hit', [
                'endpoint' => $endpoint,
                'cache_key' => $cacheKey,
            ]);
        }

        return $data;
    }

    /**
     * Invalidate track-related caches.
     */
    public function invalidateTrackCaches(int $trackId): void
    {
        $patterns = [
            $this->generateCacheKey('track_detail', ['id' => $trackId]),
            $this->generateCacheKey('track_status', ['id' => $trackId]),
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        // Invalidate tracks list caches (all variations)
        $this->invalidatePatternCaches('tracks:list:*');
        $this->invalidatePatternCaches('stats:*');
        $this->invalidatePatternCaches('dashboard:*');

        Log::info('Track caches invalidated', ['track_id' => $trackId]);
    }

    /**
     * Invalidate genre-related caches.
     */
    public function invalidateGenreCaches(): void
    {
        Cache::forget($this->generateCacheKey('genres'));
        $this->invalidatePatternCaches('tracks:list:*');
        $this->invalidatePatternCaches('stats:*');

        Log::info('Genre caches invalidated');
    }

    /**
     * Invalidate settings caches.
     */
    public function invalidateSettingsCaches(): void
    {
        Cache::forget($this->generateCacheKey('settings'));
        Log::info('Settings caches invalidated');
    }

    /**
     * Invalidate YouTube account caches.
     */
    public function invalidateYouTubeCaches(): void
    {
        Cache::forget($this->generateCacheKey('youtube_account'));
        $this->invalidatePatternCaches('stats:*');
        $this->invalidatePatternCaches('dashboard:*');

        Log::info('YouTube caches invalidated');
    }

    /**
     * Clear all application caches.
     */
    public function clearAllCaches(): void
    {
        foreach (self::CACHE_KEYS as $prefix) {
            $this->invalidatePatternCaches($prefix . ':*');
        }

        Log::info('All application caches cleared');
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStatistics(): array
    {
        $stats = [];
        
        foreach (self::CACHE_KEYS as $type => $prefix) {
            $stats[$type] = [
                'prefix' => $prefix,
                'ttl' => self::CACHE_TTL[$type] ?? 0,
                'active_keys' => $this->countCacheKeys($prefix),
            ];
        }

        return [
            'cache_driver' => config('cache.default'),
            'cache_prefix' => config('cache.prefix'),
            'types' => $stats,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate cache key.
     */
    private function generateCacheKey(string $type, array $params = []): string
    {
        $baseKey = self::CACHE_KEYS[$type] ?? $type;
        
        if (empty($params)) {
            return $baseKey;
        }

        $paramString = implode(':', array_map(function ($key, $value) {
            return $key . ':' . $value;
        }, array_keys($params), array_values($params)));

        return $baseKey . ':' . $paramString;
    }

    /**
     * Invalidate caches by pattern.
     */
    private function invalidatePatternCaches(string $pattern): void
    {
        // This is a simplified implementation
        // In production, you might want to use Redis SCAN or similar
        try {
            if (config('cache.default') === 'redis') {
                $redis = Cache::getRedis();
                $keys = $redis->keys(config('cache.prefix') . $pattern);
                
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } else {
                // For other cache drivers, we'll need to track keys manually
                // or use cache tags if supported
                Log::warning('Pattern cache invalidation not fully supported for driver: ' . config('cache.default'));
            }
        } catch (\Exception $e) {
            Log::error('Failed to invalidate pattern caches', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Count cache keys by prefix.
     */
    private function countCacheKeys(string $prefix): int
    {
        try {
            if (config('cache.default') === 'redis') {
                $redis = Cache::getRedis();
                $keys = $redis->keys(config('cache.prefix') . $prefix . ':*');
                return count($keys);
            }
            
            return 0; // Not supported for other drivers
        } catch (\Exception $e) {
            Log::error('Failed to count cache keys', [
                'prefix' => $prefix,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }
} 