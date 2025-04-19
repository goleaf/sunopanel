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
        $loggingService = app(LoggingService::class);
        $this->middleware = new LoggingMiddleware($loggingService);
        $this->request = Mockery::mock(Request::class);
        $this->request->shouldReceive('fullUrl')->andReturn('http://example.com/test');
        $this->request->shouldReceive('method')->andReturn('GET');
        $this->request->shouldReceive('ip')->andReturn('127.0.0.1');
        $this->request->shouldReceive('userAgent')->andReturn('PHPUnit');
        $this->request->shouldReceive('path')->andReturn('test');
        $this->request->shouldReceive('expectsJson')->andReturn(false);
        $this->request->shouldReceive('wantsJson')->andReturn(false);
        $this->request->shouldReceive('all')->andReturn([]);
        $this->request->shouldReceive('header')->withAnyArgs()->andReturn(null);
        Log::spy();
    }

    public function test_handle_passes_request_to_next_callable(): void
    {
        $called = false;
        $next = function ($request) use (&$called) {
            $called = true;
            $this->assertSame($this->request, $request);

            return new Response();
        };
        $this->middleware->handle($this->request, $next);
        $this->assertTrue($called);
    }

    public function test_handle_logs_exception_and_rethrows(): void
    {
        $exception = new Exception('Test exception');
        $next = function () use ($exception) {
            throw $exception;
        };
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');
        $this->middleware->handle($this->request, $next);
    }
}
