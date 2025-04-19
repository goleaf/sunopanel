<?php

namespace Tests\Unit;

use App\Http\Middleware\LoggingMiddleware;
use App\Services\Logging\LoggingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LoggingMiddlewareTest extends TestCase
{
    protected $middleware;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();

        // Use the real LoggingService rather than mocking it
        $loggingService = app(LoggingService::class);
        $this->middleware = new LoggingMiddleware($loggingService);
        $this->request = Mockery::mock(Request::class);
        
        // Common request method expectations
        $this->request->shouldReceive('fullUrl')->andReturn('http://example.com/test');
        $this->request->shouldReceive('method')->andReturn('GET');
        $this->request->shouldReceive('ip')->andReturn('127.0.0.1');
        $this->request->shouldReceive('userAgent')->andReturn('PHPUnit');
        $this->request->shouldReceive('path')->andReturn('test');
        $this->request->shouldReceive('expectsJson')->andReturn(false);
        $this->request->shouldReceive('wantsJson')->andReturn(false);
        $this->request->shouldReceive('all')->andReturn([]);
        
        // Mock the Log facade
        Log::spy();
    }

    public function testHandlePassesRequestToNextCallable(): void
    {
        // Arrange
        $called = false;
        $next = function ($request) use (&$called) {
            $called = true;
            $this->assertSame($this->request, $request);
            return new Response();
        };

        // Act
        $this->middleware->handle($this->request, $next);

        // Assert
        $this->assertTrue($called);
    }

    public function testHandleLogsExceptionAndRethrows(): void
    {
        // Arrange
        $exception = new Exception('Test exception');
        $next = function () use ($exception) {
            throw $exception;
        };
        
        // We'll verify that Log::error was called since we're using a real LoggingService
        Log::spy();

        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        // Act
        try {
            $this->middleware->handle($this->request, $next);
        } catch (Exception $e) {
            // Verify that error was logged before rethrowing
            Log::shouldHaveReceived('error')
                ->withArgs(function ($message, $data) {
                    $this->assertStringContainsString('Application error', $message);
                    $this->assertStringContainsString('Test exception', $message);
                    return true;
                });
            
            throw $e;
        }
    }
} 