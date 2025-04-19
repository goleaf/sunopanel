<?php

namespace Tests\Unit;

use App\Http\Middleware\ErrorLoggingMiddleware;
use App\Services\Logging\ErrorLogService;
use Exception;
use Illuminate\Http\Request;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ErrorLoggingMiddlewareTest extends TestCase
{
    protected $errorLogService;
    protected $middleware;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->errorLogService = Mockery::mock(ErrorLogService::class);
        $this->middleware = new ErrorLoggingMiddleware($this->errorLogService);
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

        $this->errorLogService->shouldReceive('logException')
            ->once()
            ->with(
                $exception, 
                'HTTP Request', 
                [
                    'url' => 'http://example.com/test',
                    'method' => 'GET',
                    'ip' => '127.0.0.1',
                    'user_id' => 'guest',
                ]
            );

        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        // Act
        $this->middleware->handle($this->request, $next);
    }
} 