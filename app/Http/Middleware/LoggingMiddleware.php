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
        // Log request info before processing
        $this->loggingService->logInfoMessage('Incoming Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id, // Add user ID if authenticated
        ]);

        try {
            $response = $next($request);

            // Log response info after processing
            $this->loggingService->logInfoMessage('Outgoing Response', [
                'status_code' => $response->getStatusCode(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
            ]);

            return $response;
        } catch (\Exception $e) {
            // Log exception details
            $this->loggingService->logErrorMessage('Request Exception', [
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
            ]);

            // Rethrow the exception to let Laravel's handler manage it
            throw $e;
        }
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
