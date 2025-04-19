<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Services\Logging\LoggingService;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class Handler extends ExceptionHandler
{
    /**
     * The error log service
     */
    private LoggingService $loggingService;

    /**
     * Constructor
     */
    public function __construct(LoggingService $loggingService)
    {
        parent::__construct(app());
        $this->loggingService = $loggingService;
    }

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $exception) {
            // Don't log if the exception shouldn't be reported
            if (!$this->shouldReport($exception)) {
                return;
            }
        });
    }

    /**
     * Report or log an exception.
     *
     * @throws Throwable
     */
    public function report(Throwable $e): void
    {
        if ($this->shouldReport($e)) {
            $this->loggingService->logError($e, null, 'Exception Handler');
        }

        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Throwable $e
     * @return Response
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e): Response
    {
        // For API requests, format the error response as JSON
        if ($request->expectsJson() || 
            str_starts_with($request->path(), 'api/') || 
            $request->wantsJson()) {
            
            $this->loggingService->logApiError($e, $request, null, 'API exception handler');
            
            $statusCode = $this->isHttpException($e) ? $e->getStatusCode() : 500;
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request.',
                'error' => $this->convertExceptionToArray($e),
                'status_code' => $statusCode
            ], $statusCode);
        }
        
        return parent::render($request, $e);
    }
} 