<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Genre;
use App\Models\Track;
use App\Models\Playlist;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Service for caching frequently accessed data
 */
final class CacheService
{
    /**
     * Cache time-to-live in seconds (24 hours)
     */
    private const CACHE_TTL = 86400;
    
    /**
     * Get all genres from cache or database
     */
    public function getAllGenres(): Collection
    {
        return Cache::remember('all_genres', self::CACHE_TTL, function () {
            return Genre::orderBy('name')->get();
        });
    }
    
    /**
     * Get popular tracks from cache or database
     */
    public function getPopularTracks(int $limit = 10): Collection
    {
        $cacheKey = 'popular_tracks_' . $limit;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($limit) {
            return Track::with('genres')
                ->orderBy('play_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }
    
    /**
     * Get recently added tracks from cache or database
     */
    public function getRecentTracks(int $limit = 10): Collection
    {
        $cacheKey = 'recent_tracks_' . $limit;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($limit) {
            return Track::with('genres')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }
    
    /**
     * Get popular playlists from cache or database
     */
    public function getPopularPlaylists(int $limit = 5): Collection
    {
        $cacheKey = 'popular_playlists_' . $limit;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($limit) {
            return Playlist::withCount('tracks')
                ->orderBy('tracks_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }
    
    /**
     * Clear cache when data is updated
     */
    public function clearCache(string $type = 'all'): void
    {
        switch ($type) {
            case 'genres':
                Cache::forget('all_genres');
                break;
            case 'tracks':
                // Clear all track-related caches
                Cache::forget('popular_tracks_10');
                Cache::forget('recent_tracks_10');
                Cache::forget('popular_tracks_5');
                Cache::forget('recent_tracks_5');
                // Additional common limits
                Cache::forget('popular_tracks_20');
                Cache::forget('recent_tracks_20');
                break;
            case 'playlists':
                Cache::forget('popular_playlists_5');
                Cache::forget('popular_playlists_10');
                break;
            case 'all':
            default:
                // Clear all caches
                Cache::flush();
                break;
        }
    }
} 