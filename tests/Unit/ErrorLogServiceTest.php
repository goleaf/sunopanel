<?php

namespace Tests\Unit;

use App\Services\Logging\ErrorLogService;
use Exception;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ErrorLogServiceTest extends TestCase
{
    public function testLogMethodLogsWithCorrectLevel(): void
    {
        // Arrange
        Log::shouldReceive('error')
            ->once()
            ->with('Test error message', ['test' => 'context']);

        $service = new ErrorLogService();

        // Act
        $service->log('Test error message', ['test' => 'context'], 'error');
    }

    public function testLogMethodRemovesSensitiveData(): void
    {
        // Arrange
        Log::shouldReceive('error')
            ->once()
            ->with('Test with sensitive data', ['public' => 'data']);

        $service = new ErrorLogService();

        // Act
        $service->log(
            'Test with sensitive data', 
            [
                'public' => 'data',
                'password' => 'secret123',
                'token' => 'abc123',
                'secret' => 'confidential',
                'api_key' => '12345',
                'auth' => 'bearer token'
            ],
            'error'
        );
    }

    public function testLogExceptionLogsWithCorrectContext(): void
    {
        // Arrange
        $exception = new Exception('Test exception');
        $location = 'TestController@testMethod';
        $additionalContext = ['user_id' => 123];

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function($message, $context) use ($exception, $location) {
                return 
                    strpos($message, "Exception in {$location}") !== false &&
                    $context['message'] === $exception->getMessage() &&
                    $context['code'] === $exception->getCode() &&
                    isset($context['file']) &&
                    isset($context['line']) &&
                    isset($context['trace']) &&
                    $context['location'] === $location &&
                    $context['user_id'] === 123;
            });

        $service = new ErrorLogService();

        // Act
        $service->logException($exception, $location, $additionalContext);
    }
} 