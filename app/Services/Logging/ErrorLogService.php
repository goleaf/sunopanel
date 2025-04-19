<?php

declare(strict_types=1);

namespace App\Services\Logging;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use Symfony\Component\HttpFoundation\Response;

final readonly class ErrorLogService
{
    /**
     * Log an application error with standardized format
     */
    public function logError(
        Throwable $exception, 
        ?Request $request = null, 
        ?string $context = null, 
        ?int $userId = null
    ): void {
        $logData = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->formatTrace($exception->getTrace()),
            'user_id' => $userId ?? auth()->id(),
            'context' => $context,
        ];

        // Add request data if available
        if ($request) {
            $logData['request_method'] = $request->method();
            $logData['request_url'] = $request->fullUrl();
            $logData['request_ip'] = $request->ip();
            $logData['request_referrer'] = $request->header('referer');
            $logData['request_user_agent'] = $request->userAgent();
            
            // Include request data, but filter out sensitive information
            $logData['request_data'] = $this->filterSensitiveData($request->all());
        }

        Log::error('Application error: ' . $exception->getMessage(), $logData);
    }

    /**
     * Log an API error with standardized format
     */
    public function logApiError(
        Throwable $exception, 
        Request $request, 
        Response $response = null, 
        ?string $context = null
    ): void {
        $logData = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->formatTrace($exception->getTrace()),
            'user_id' => auth()->id(),
            'context' => $context,
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
            'request_ip' => $request->ip(),
            'api_version' => $request->header('Accept-Version'),
            'request_data' => $this->filterSensitiveData($request->all()),
        ];

        if ($response) {
            $logData['response_status'] = $response->getStatusCode();
            $logData['response_content'] = $response->getContent();
        }

        Log::error('API error: ' . $exception->getMessage(), $logData);
    }

    /**
     * Log a database error with standardized format
     */
    public function logDatabaseError(
        Throwable $exception, 
        ?string $query = null, 
        ?array $bindings = null, 
        ?string $context = null
    ): void {
        $logData = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->formatTrace($exception->getTrace()),
            'user_id' => auth()->id(),
            'context' => $context,
        ];

        if ($query) {
            $logData['query'] = $query;
            if ($bindings) {
                $logData['bindings'] = $bindings;
            }
        }

        Log::error('Database error: ' . $exception->getMessage(), $logData);
    }

    /**
     * Format the stack trace to be more readable and remove sensitive information
     */
    private function formatTrace(array $trace): array
    {
        return array_map(function($item) {
            // Keep only relevant information and filter out arguments
            return [
                'file' => $item['file'] ?? null,
                'line' => $item['line'] ?? null,
                'function' => $item['function'] ?? null,
                'class' => $item['class'] ?? null,
                'type' => $item['type'] ?? null,
            ];
        }, array_slice($trace, 0, 10)); // Limit to first 10 entries for brevity
    }

    /**
     * Filter out sensitive data from the request payload
     */
    private function filterSensitiveData(array $data): array
    {
        $sensitiveFields = [
            'password', 
            'password_confirmation', 
            'token', 
            'access_token', 
            'secret', 
            'credit_card', 
            'card_number',
            'api_key',
            'api_secret',
            'auth_key',
            'secret_key',
            'private_key',
            'ssn',
            'social_security',
            'cvv'
        ];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->filterSensitiveData($value);
            } elseif (in_array(strtolower($key), $sensitiveFields, true)) {
                $data[$key] = '[FILTERED]';
            }
        }

        return $data;
    }
} 