<?php

namespace Tests\Unit;

use App\Http\Middleware\LoggingMiddleware;
use App\Services\Logging\LoggingService;
use Exception;
use Illuminate\Http\Request;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LoggingMiddlewareTest extends TestCase
{
    protected $loggingService;
    protected $middleware;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggingService = Mockery::mock(LoggingService::class);
        $this->middleware = new LoggingMiddleware($this->loggingService);
        $this->request = Mockery::mock(Request::class);
        
        // Common request method expectations
        $this->request->shouldReceive('fullUrl')->andReturn('http://example.com/test');
        $this->request->shouldReceive('method')->andReturn('GET');
        $this->request->shouldReceive('ip')->andReturn('127.0.0.1');
        $this->request->shouldReceive('user')->andReturn(null);
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

        $this->loggingService->shouldReceive('logError')
            ->once()
            ->with(
                $exception, 
                $this->request,
                'Middleware exception handler'
            );

        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        // Act
        $this->middleware->handle($this->request, $next);
    }
} 