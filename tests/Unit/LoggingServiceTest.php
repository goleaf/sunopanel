<?php

namespace Tests\Unit;

use App\Services\Logging\LoggingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class LoggingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Auth::shouldReceive('id')->andReturn(null);
    }

    public function test_log_error_method_logs_with_correct_context(): void
    {
        $exception = new Exception('Test exception');

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($exception) {
                return 
                    $message === 'Test exception' && 
                    isset($context['exception']) && 
                    isset($context['custom_context']) && 
                    $context['custom_context'] === 'Test context';
            });

        $service = new LoggingService;
        $service->logError($exception, null, 'Test context');
    }

    public function test_log_api_error_with_request_details(): void
    {
        $exception = new Exception('API error');
        $request = Mockery::mock(Request::class);

        $request->shouldReceive('method')->andReturn('GET');
        $request->shouldReceive('fullUrl')->andReturn('http://example.com/api/test');
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('header')->with('Accept-Version')->andReturn('1.0');
        $request->shouldReceive('all')->andReturn(['public' => 'data']);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return 
                    $message === 'API error' &&
                    isset($context['request_method']) &&
                    $context['request_method'] === 'GET' &&
                    isset($context['request_url']) &&
                    isset($context['client_ip']) &&
                    isset($context['request_data']);
            });
            
        $service = new LoggingService;
        $service->logApiError($exception, $request, null, 'API test');
    }

    public function test_filters_sensitive_data_from_request(): void
    {
        $exception = new Exception('API error');
        $request = Mockery::mock(Request::class);

        $request->shouldReceive('method')->andReturn('POST');
        $request->shouldReceive('fullUrl')->andReturn('http://example.com/api/user');
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('header')->with('Accept-Version')->andReturn('1.0');
        $request->shouldReceive('all')->andReturn([
            'email' => 'test@example.com',
            'password' => 'secret123',
            'token' => 'abc123',
            'api_key' => '12345',
            'credit_card' => '4111111111111111',
        ]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return
                    isset($context['request_data']['email']) &&
                    $context['request_data']['email'] === 'test@example.com' &&
                    $context['request_data']['password'] === '[FILTERED]' &&
                    $context['request_data']['token'] === '[FILTERED]' &&
                    $context['request_data']['api_key'] === '[FILTERED]' &&
                    $context['request_data']['credit_card'] === '[FILTERED]';
            });

        $service = new LoggingService;
        $service->logApiError($exception, $request);
    }

    public function test_log_database_error_with_query_details(): void
    {
        $exception = new Exception('Database error');
        $query = 'SELECT * FROM users WHERE email = ?';
        $bindings = ['test@example.com'];

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($query, $bindings) {
                return 
                    $message === 'Database error' &&
                    isset($context['query']) &&
                    $context['query'] === $query &&
                    isset($context['bindings']) &&
                    $context['bindings'] === $bindings &&
                    isset($context['custom_context']) &&
                    $context['custom_context'] === 'DB test';
            });

        $service = new LoggingService;
        $service->logDatabaseError($exception, $query, $bindings, 'DB test');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
