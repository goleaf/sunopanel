<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Logging\LoggingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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
        try {
            // For API requests, log the request data
            if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
                $this->loggingService->info('API request received', [
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            $response = $next($request);

            // Log failed responses (status >= 400) for API requests
            if (($request->expectsJson() ||
                str_starts_with($request->path(), 'api/') ||
                $request->wantsJson()) &&
                $response->getStatusCode() >= 400) {

                $responseData = json_decode($response->getContent(), true);
                $this->loggingService->warning(
                    "API response with status {$response->getStatusCode()}",
                    [
                        'status' => $response->getStatusCode(),
                        'path' => $request->path(),
                        'method' => $request->method(),
                        'response' => $responseData,
                    ]
                );
            }

            return $response;
        } catch (Throwable $exception) {
            // Log the exception using our logging service
            $this->loggingService->logError(
                $exception,
                $request,
                'Middleware exception handler'
            );

            // Re-throw the exception to be handled by the global exception handler
            throw $exception;
        }
    }
}
