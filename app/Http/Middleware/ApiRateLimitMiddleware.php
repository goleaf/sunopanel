<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class ApiRateLimitMiddleware
{
    /**
     * Rate limit configurations for different endpoints.
     */
    private array $rateLimits = [
        'default' => ['requests' => 60, 'window' => 60], // 60 requests per minute
        'tracks' => ['requests' => 100, 'window' => 60], // 100 requests per minute for tracks
        'youtube' => ['requests' => 30, 'window' => 60], // 30 requests per minute for YouTube
        'bulk' => ['requests' => 10, 'window' => 60], // 10 requests per minute for bulk operations
        'upload' => ['requests' => 5, 'window' => 60], // 5 requests per minute for uploads
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $type = 'default'): Response
    {
        $clientId = $this->getClientIdentifier($request);
        $limit = $this->rateLimits[$type] ?? $this->rateLimits['default'];
        
        $cacheKey = "rate_limit:{$type}:{$clientId}";
        $windowStart = now()->startOfMinute()->timestamp;
        $windowKey = "{$cacheKey}:{$windowStart}";

        // Get current request count for this window
        $currentCount = Cache::get($windowKey, 0);

        // Check if limit exceeded
        if ($currentCount >= $limit['requests']) {
            Log::warning('Rate limit exceeded', [
                'client_id' => $clientId,
                'type' => $type,
                'current_count' => $currentCount,
                'limit' => $limit['requests'],
                'window' => $limit['window'],
                'endpoint' => $request->path(),
                'method' => $request->method(),
            ]);

            return $this->rateLimitExceededResponse($limit);
        }

        // Increment counter
        Cache::put($windowKey, $currentCount + 1, $limit['window']);

        // Add rate limit headers to response
        $response = $next($request);
        
        $remaining = max(0, $limit['requests'] - ($currentCount + 1));
        $resetTime = now()->startOfMinute()->addMinute()->timestamp;

        $response->headers->set('X-RateLimit-Limit', (string) $limit['requests']);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
        $response->headers->set('X-RateLimit-Reset', (string) $resetTime);
        $response->headers->set('X-RateLimit-Window', (string) $limit['window']);

        return $response;
    }

    /**
     * Get client identifier for rate limiting.
     */
    private function getClientIdentifier(Request $request): string
    {
        // Use IP address as the primary identifier
        $ip = $request->ip();
        
        // Add user agent for additional uniqueness
        $userAgent = substr(md5($request->userAgent() ?? ''), 0, 8);
        
        return "{$ip}:{$userAgent}";
    }

    /**
     * Return rate limit exceeded response.
     */
    private function rateLimitExceededResponse(array $limit): Response
    {
        $resetTime = now()->startOfMinute()->addMinute()->timestamp;
        
        $response = response()->json([
            'success' => false,
            'message' => 'Rate limit exceeded',
            'errors' => [
                'rate_limit' => [
                    'limit' => $limit['requests'],
                    'window' => $limit['window'],
                    'reset_at' => $resetTime,
                    'retry_after' => now()->startOfMinute()->addMinute()->diffInSeconds(now()),
                ],
            ],
            'timestamp' => now()->toISOString(),
        ], Response::HTTP_TOO_MANY_REQUESTS);

        $response->headers->set('X-RateLimit-Limit', (string) $limit['requests']);
        $response->headers->set('X-RateLimit-Remaining', '0');
        $response->headers->set('X-RateLimit-Reset', (string) $resetTime);
        $response->headers->set('X-RateLimit-Window', (string) $limit['window']);
        $response->headers->set('Retry-After', (string) now()->startOfMinute()->addMinute()->diffInSeconds(now()));

        return $response;
    }
} 