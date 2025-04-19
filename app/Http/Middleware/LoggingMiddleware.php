<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Logging\LoggingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class LoggingMiddleware
{
    /**
     * The logging service instance.
     */
    private readonly LoggingService $loggingService;

    /**
     * Create a new middleware instance.
     *
     * @param  LoggingService  $loggingService  The logging service
     */
    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  The request instance
     * @param  Closure  $next  The next middleware
     * @return Response The response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log API request info (conditionally)
        if ($this->isApiRequest($request)) {
            $this->loggingService->info('API request received', [
                'path' => $request->path(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        $response = $next($request);

        // Log failed API responses (conditionally)
        if ($this->isApiRequest($request) && $response->getStatusCode() >= 400) {
            $responseData = json_decode($response->getContent(), true);
            $this->loggingService->warning(
                "API response with status {$response->getStatusCode()}",
                [
                    'status' => $response->getStatusCode(),
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'response' => $responseData, // Keep response logging for now, but could be revisited
                ]
            );
        }

        return $response;
    }

    /**
     * Determine if the request is likely an API request.
     */
    private function isApiRequest(Request $request): bool
    {
        // Consider refining this logic, e.g., checking route group
        return $request->expectsJson() ||
               str_starts_with($request->path(), 'api/') ||
               $request->wantsJson();
    }
}
