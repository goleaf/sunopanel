<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Logging;

use App\Services\Logging\ErrorLogService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\TestCase;
use Mockery;

final class ErrorLogServiceTest extends TestCase
{
    private ErrorLogService $errorLogService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->errorLogService = new ErrorLogService();
        
        // Mock the Log facade
        Log::shouldReceive('error')
            ->byDefault()
            ->andReturnNull();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_logs_an_error_with_exception_details(): void
    {
        // Given
        $exception = new Exception('Test exception');
        $context = 'Test context';
        
        // Expect
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $data) use ($exception, $context) {
                $this->assertStringContainsString('Application error', $message);
                $this->assertStringContainsString('Test exception', $message);
                $this->assertEquals(get_class($exception), $data['exception']);
                $this->assertEquals($exception->getMessage(), $data['message']);
                $this->assertEquals($exception->getCode(), $data['code']);
                $this->assertEquals($context, $data['context']);
                return true;
            });
        
        // When
        $this->errorLogService->logError($exception, null, $context);
    }

    /** @test */
    public function it_logs_an_api_error_with_request_details(): void
    {
        // Given
        $exception = new Exception('API test exception');
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('method')->andReturn('POST');
        $request->shouldReceive('fullUrl')->andReturn('https://example.com/api/test');
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('header')->with('Accept-Version')->andReturn('1.0');
        $request->shouldReceive('all')->andReturn(['test' => 'data']);
        
        // Expect
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $data) use ($exception, $request) {
                $this->assertStringContainsString('API error', $message);
                $this->assertStringContainsString('API test exception', $message);
                $this->assertEquals(get_class($exception), $data['exception']);
                $this->assertEquals($exception->getMessage(), $data['message']);
                $this->assertEquals('POST', $data['request_method']);
                $this->assertEquals('https://example.com/api/test', $data['request_url']);
                $this->assertEquals('127.0.0.1', $data['request_ip']);
                $this->assertEquals('1.0', $data['api_version']);
                return true;
            });
        
        // When
        $this->errorLogService->logApiError($exception, $request, null, 'API test');
    }

    /** @test */
    public function it_logs_a_database_error_with_query_details(): void
    {
        // Given
        $exception = new Exception('Database test exception');
        $query = 'SELECT * FROM users WHERE id = ?';
        $bindings = [1];
        
        // Expect
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $data) use ($exception, $query, $bindings) {
                $this->assertStringContainsString('Database error', $message);
                $this->assertStringContainsString('Database test exception', $message);
                $this->assertEquals(get_class($exception), $data['exception']);
                $this->assertEquals($exception->getMessage(), $data['message']);
                $this->assertEquals($query, $data['query']);
                $this->assertEquals($bindings, $data['bindings']);
                return true;
            });
        
        // When
        $this->errorLogService->logDatabaseError($exception, $query, $bindings, 'DB test');
    }

    /** @test */
    public function it_filters_sensitive_data_from_request(): void
    {
        // Given
        $exception = new Exception('Test exception');
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('method')->andReturn('POST');
        $request->shouldReceive('fullUrl')->andReturn('https://example.com/api/login');
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
        
        // Expect
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $data) {
                $this->assertEquals('[FILTERED]', $data['request_data']['password']);
                $this->assertEquals('[FILTERED]', $data['request_data']['credit_card']);
                $this->assertEquals('[FILTERED]', $data['request_data']['nested']['api_key']);
                $this->assertEquals('test@example.com', $data['request_data']['email']);
                return true;
            });
        
        // When
        $this->errorLogService->logApiError($exception, $request);
    }
} 