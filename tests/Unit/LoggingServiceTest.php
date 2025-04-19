<?php

namespace Tests\Unit;

use App\Services\Logging\LoggingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LoggingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn(null);
    }
    
    public function testLogErrorMethodLogsWithCorrectContext(): void
    {
        // Arrange
        $exception = new Exception('Test exception');
        
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function($message, $context) use ($exception) {
                return 
                    strpos($message, 'Application error: Test exception') !== false &&
                    $context['exception'] === get_class($exception) &&
                    $context['message'] === $exception->getMessage() &&
                    $context['code'] === $exception->getCode() &&
                    isset($context['file']) &&
                    isset($context['line']) &&
                    isset($context['trace']);
            });

        $service = new LoggingService();

        // Act
        $service->logError($exception, null, 'Test context');
    }

    public function testLogApiErrorWithRequestDetails(): void
    {
        // Arrange
        $exception = new Exception('API error');
        $request = Mockery::mock(Request::class);
        
        $request->shouldReceive('method')->andReturn('GET');
        $request->shouldReceive('fullUrl')->andReturn('http://example.com/api/test');
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('header')->with('Accept-Version')->andReturn('1.0');
        $request->shouldReceive('all')->andReturn(['public' => 'data']);
        
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function($message, $context) use ($exception) {
                return 
                    strpos($message, 'API error: API error') !== false &&
                    $context['exception'] === get_class($exception) &&
                    $context['message'] === $exception->getMessage() &&
                    $context['request_method'] === 'GET' &&
                    $context['request_url'] === 'http://example.com/api/test';
            });

        $service = new LoggingService();

        // Act
        $service->logApiError($exception, $request, null, 'API test');
    }

    public function testFiltersSensitiveDataFromRequest(): void
    {
        // Arrange
        $exception = new Exception('API error');
        $request = Mockery::mock(Request::class);
        
        $request->shouldReceive('method')->andReturn('POST');
        $request->shouldReceive('fullUrl')->andReturn('http://example.com/api/login');
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('header')->with('Accept-Version')->andReturn('1.0');
        $request->shouldReceive('all')->andReturn([
            'email' => 'test@example.com',
            'password' => 'secret123',
            'token' => 'abc123',
            'api_key' => '12345',
            'credit_card' => '4111111111111111'
        ]);
        
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function($message, $context) {
                // The password and other sensitive fields should be filtered
                return 
                    isset($context['request_data']['email']) &&
                    $context['request_data']['email'] === 'test@example.com' &&
                    $context['request_data']['password'] === '[FILTERED]' &&
                    $context['request_data']['token'] === '[FILTERED]' &&
                    $context['request_data']['api_key'] === '[FILTERED]' &&
                    $context['request_data']['credit_card'] === '[FILTERED]';
            });

        $service = new LoggingService();

        // Act
        $service->logApiError($exception, $request);
    }
    
    public function testLogDatabaseErrorWithQueryDetails(): void
    {
        // Arrange
        $exception = new Exception('Database error');
        $query = 'SELECT * FROM users WHERE email = ?';
        $bindings = ['test@example.com'];
        
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function($message, $context) use ($exception, $query, $bindings) {
                return 
                    strpos($message, 'Database error: Database error') !== false &&
                    $context['exception'] === get_class($exception) &&
                    $context['message'] === $exception->getMessage() &&
                    $context['query'] === $query &&
                    $context['bindings'] === $bindings;
            });

        $service = new LoggingService();

        // Act
        $service->logDatabaseError($exception, $query, $bindings, 'DB test');
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 