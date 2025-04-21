<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheSSRContent
{
    /**
     * Routes that should be cached
     */
    protected array $cachableRoutes = [
        'dashboard',
        'tracks.index',
        'genres.index',
        'playlists.index',
        'system.stats',
    ];

    /**
     * Cache TTL in minutes - 60 minutes by default
     */
    protected int $cacheTtl = 60;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Don't cache for authenticated users or non-GET requests
        if ($request->method() !== 'GET' || $request->user()) {
            return $next($request);
        }
        
        // Check if current route is cachable
        $routeName = $request->route()->getName();
        if (!in_array($routeName, $this->cachableRoutes)) {
            return $next($request);
        }
        
        // Generate a cache key based on full URL
        $cacheKey = 'ssr_' . sha1($request->fullUrl());
        
        // Try to get from cache
        if (Cache::has($cacheKey)) {
            $cachedContent = Cache::get($cacheKey);
            
            // If we have cached content, return it
            if ($cachedContent) {
                $response = new Response($cachedContent['content'], 200);
                
                // Add cache headers
                $response->headers->add([
                    'X-SSR-Cache' => 'HIT',
                    'Cache-Control' => 'public, max-age=' . ($this->cacheTtl * 60),
                ]);
                
                // Add original headers from cached response
                foreach ($cachedContent['headers'] as $name => $value) {
                    $response->headers->set($name, $value);
                }
                
                return $response;
            }
        }
        
        // No cache hit, get response
        $response = $next($request);
        
        // Only cache successful responses
        if ($response->getStatusCode() === 200) {
            // Get content and headers
            $content = $response->getContent();
            $headers = $response->headers->all();
            
            // Store in cache
            Cache::put($cacheKey, [
                'content' => $content, 
                'headers' => $headers
            ], $this->cacheTtl * 60);
            
            // Add header to indicate a cache miss
            $response->headers->add([
                'X-SSR-Cache' => 'MISS',
                'Cache-Control' => 'public, max-age=' . ($this->cacheTtl * 60),
            ]);
        }
        
        return $response;
    }
} 