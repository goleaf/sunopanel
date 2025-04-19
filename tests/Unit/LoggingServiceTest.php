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
        $exception = new Exception('Test Exception');

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($exception) {
                $this->assertEquals('Application error: Test Exception', $message);
                $this->assertEquals(get_class($exception), $context['exception']);
                $this->assertEquals('Test context', $context['context']);
                $this->assertArrayHasKey('trace', $context);
                $this->assertIsArray($context['trace']);
                return true;
            });

        $service = new LoggingService;
        $service->logStandardError($exception, null, 'Test context');
    }

    public function test_log_api_error_with_request_details(): void
    {
        $exception = new Exception('API error');
        $request = Request::create('http://example.com/api/test', 'GET');
        $request->headers->set('Accept-Version', '1.0');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->setLaravelSession($this->app['session.store']);
        
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($exception, $request) {
                $this->assertStringContainsString('API error', $message);
                $this->assertEquals(get_class($exception), $context['exception']);
                $this->assertEquals('API test', $context['context']);
                $this->assertEquals('GET', $context['request_method']);
                $this->assertEquals('http://example.com/api/test', $context['request_url']);
                $this->assertEquals('127.0.0.1', $context['request_ip']);
                $this->assertEquals('1.0', $context['api_version']);
                $this->assertArrayHasKey('request_data', $context);
                return true;
            });

        $service = new LoggingService;
        $service->logApiError($exception, $request, null, 'API test');
    }

    public function test_filters_sensitive_data_from_request(): void
    {
        $requestData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'token' => 'sensitive_token',
            'nested' => [
                'card_number' => '1234-5678-9012-3456',
                'secret_key' => 'supersecret'
            ]
        ];

        $request = Request::create('/', 'POST', $requestData);

        $service = new LoggingService();
        
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('filterSensitiveData');
        $method->setAccessible(true);

        $filteredData = $method->invoke($service, $requestData);
        
        $this->assertEquals('[FILTERED]', $filteredData['password']);
        $this->assertEquals('[FILTERED]', $filteredData['token']);
        $this->assertEquals('[FILTERED]', $filteredData['nested']['card_number']);
        $this->assertEquals('[FILTERED]', $filteredData['nested']['secret_key']);
        $this->assertEquals('John Doe', $filteredData['name']);
    }

    public function test_log_database_error_with_query_details(): void
    {
        $exception = new Exception('Database error');
        $query = 'SELECT * FROM users WHERE email = ?';
        $bindings = ['test@example.com'];

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($exception, $query, $bindings) {
                $this->assertStringContainsString('Database error', $message);
                $this->assertEquals(get_class($exception), $context['exception']);
                $this->assertEquals('DB test', $context['context']);
                $this->assertEquals($query, $context['query']);
                $this->assertEquals($bindings, $context['bindings']);
                return true;
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
