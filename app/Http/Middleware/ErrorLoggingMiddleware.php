<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Logging\ErrorLogService;
use Closure;
use Illuminate\Http\Request;
use Throwable;
use Symfony\Component\HttpFoundation\Response;

final class ErrorLoggingMiddleware
{
    private ErrorLogService $errorLogService;

    public function __construct(ErrorLogService $errorLogService)
    {
        $this->errorLogService = $errorLogService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            return $next($request);
        } catch (Throwable $exception) {
            // Determine if this is an API request
            $isApiRequest = $request->expectsJson() || 
                str_starts_with($request->path(), 'api/') || 
                $request->wantsJson();
            
            if ($isApiRequest) {
                $this->errorLogService->logApiError(
                    $exception, 
                    $request, 
                    null, 
                    'API request failed in middleware'
                );
                
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing your request.',
                    'error' => $exception->getMessage(),
                    'status_code' => 500
                ], 500);
            }
            
            $this->errorLogService->logError(
                $exception, 
                $request, 
                'Request failed in middleware'
            );
            
            // Re-throw the exception for Laravel's exception handler
            throw $exception;
        }
    }
} 