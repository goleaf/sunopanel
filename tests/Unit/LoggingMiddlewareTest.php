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
use App\Models\User;
use Mockery\MockInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoggingMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private LoggingServiceInterface $loggingServiceMock;
    private LoggingMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loggingServiceMock = Mockery::mock(LoggingServiceInterface::class);
        $this->middleware = new LoggingMiddleware($this->loggingServiceMock);
    }

    #[Test]
    public function handle_passes_request_to_next_callable(): void
    {
        // Arrange
        $request = Mockery::mock(Request::class);
        $response = Mockery::mock(Response::class);
        $next = fn() => $response;

        // Expectations
        $request->shouldReceive('method')->andReturn('GET');
        $request->shouldReceive('fullUrl')->times(2)->andReturn('http://test.com');
        $request->shouldReceive('ip')->times(2)->andReturn('127.0.0.1');
        $request->shouldReceive('userAgent')->andReturn('TestAgent');
        $request->shouldReceive('user')->times(2)->andReturn(null); // Expect user() call, return null (no auth)

        $this->loggingServiceMock->shouldReceive('logInfoMessage')->twice(); // Expect info logs
        $response->shouldReceive('getStatusCode')->andReturn(200); 

        // Act
        $result = $this->middleware->handle($request, $next);

        // Assert
        $this->assertSame($response, $result);
    }

    #[Test]
    public function handle_logs_exception_and_rethrows(): void
    {
        // Arrange
        $request = Mockery::mock(Request::class);
        $exception = new Exception('Test exception');
        $next = fn() => throw $exception;

        // Expectations
        $request->shouldReceive('method')->andReturn('POST');
        $request->shouldReceive('fullUrl')->times(2)->andReturn('http://test.com/error');
        $request->shouldReceive('ip')->times(2)->andReturn('192.168.1.1');
        $request->shouldReceive('userAgent')->andReturn('ErrorAgent');
        $request->shouldReceive('user')->times(2)->andReturn(null); // Expect user() call

        $this->loggingServiceMock->shouldReceive('logInfoMessage')->once(); // Expect request info log
        $this->loggingServiceMock->shouldReceive('logErrorMessage') // Expect error log
            ->once()
            ->withArgs(function (string $message, array $context) use ($exception, $request) {
                return $message === 'Request Exception' &&
                       $context['exception_class'] === get_class($exception) &&
                       $context['message'] === $exception->getMessage() &&
                       $context['url'] === 'http://test.com/error' && // Use the mocked URL
                       $context['ip'] === '192.168.1.1'; // Use the mocked IP
            });

        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        // Act
        $this->middleware->handle($request, $next);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
