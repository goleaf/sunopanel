<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Logging;

use App\Services\Logging\LoggingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

final class LoggingServiceTest extends TestCase
{
    private LoggingService $loggingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loggingService = app(LoggingService::class);
        Log::spy();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_logs_an_error_with_exception_details(): void
    {
        $exception = new Exception('Test exception');
        $context = 'Test context';
        $this->loggingService->logStandardError($exception, null, $context);
        
        Log::shouldHaveReceived('error')
            ->withArgs(function ($message, $data) use ($exception, $context) {
                $this->assertStringContainsString('Test exception', $message);
                $this->assertEquals(get_class($exception), $data['exception']);
                $this->assertEquals($context, $data['context']);
                $this->assertArrayHasKey('trace', $data);
                return true;
            });
    }

    #[Test]
    public function it_logs_an_api_error_with_request_details(): void
    {
        $exception = new Exception('API test exception');
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('method')->andReturn('POST');
        $request->shouldReceive('fullUrl')->andReturn('https://example.com/api/test');
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('header')->with('Accept-Version')->andReturn('1.0');
        $request->shouldReceive('all')->andReturn(['test' => 'data']);
        
        $this->loggingService->logApiError($exception, $request, null, 'API test');
        
        Log::shouldHaveReceived('error')
            ->withArgs(function ($message, $data) use ($exception) {
                $this->assertStringContainsString('API test exception', $message);
                $this->assertEquals(get_class($exception), $data['exception']);
                $this->assertEquals($exception->getMessage(), $data['message']);
                $this->assertEquals('POST', $data['request_method']);
                $this->assertEquals('https://example.com/api/test', $data['request_url']);
                $this->assertEquals('127.0.0.1', $data['request_ip']);
                $this->assertEquals('1.0', $data['api_version']);
                return true;
            });
    }

    #[Test]
    public function it_logs_a_database_error_with_query_details(): void
    {
        $exception = new Exception('Database test exception');
        $query = 'SELECT * FROM users WHERE id = ?';
        $bindings = [1];
        
        $this->loggingService->logDatabaseError($exception, $query, $bindings, 'DB test');
        
        Log::shouldHaveReceived('error')
            ->withArgs(function ($message, $data) use ($exception, $query, $bindings) {
                $this->assertStringContainsString('Database test exception', $message);
                $this->assertEquals(get_class($exception), $data['exception']);
                $this->assertEquals($exception->getMessage(), $data['message']);
                $this->assertEquals($query, $data['query']);
                $this->assertEquals($bindings, $data['bindings']);
                return true;
            });
    }

    #[Test]
    public function it_filters_sensitive_data_from_request(): void
    {
        $exception = new Exception('Test exception');
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('method')->andReturn('POST');
        $request->shouldReceive('fullUrl')->andReturn('https://example.com/api/users');
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('header')->andReturn(null);
        $request->shouldReceive('all')->andReturn([
            'email' => 'test@example.com',
            'password' => 'secret123',
            'credit_card' => '4111111111111111',
            'nested' => [
                'api_key' => 'secret-api-key'
            ]
        ]);
        
        $this->loggingService->logApiError($exception, $request);
        
        Log::shouldHaveReceived('error')
            ->withArgs(function ($message, $data) {
                $this->assertEquals('[FILTERED]', $data['request_data']['password']);
                $this->assertEquals('[FILTERED]', $data['request_data']['credit_card']);
                $this->assertEquals('[FILTERED]', $data['request_data']['nested']['api_key']);
                $this->assertEquals('test@example.com', $data['request_data']['email']);
                return true;
            });
    }
} 