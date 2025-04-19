<?php

namespace Tests\Feature;

use App\Services\NotificationService;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{

    public function test_success_method_sets_flash_message(): void
    {
        $service = new NotificationService();
        $service->success('Success message');
        
        $this->assertEquals('Success message', Session::get('success'));
    }

    public function test_error_method_sets_flash_message(): void
    {
        $service = new NotificationService();
        $service->error('Error message');
        
        $this->assertEquals('Error message', Session::get('error'));
    }

    public function test_warning_method_sets_flash_message(): void
    {
        $service = new NotificationService();
        $service->warning('Warning message');
        
        $this->assertEquals('Warning message', Session::get('warning'));
    }

    public function test_info_method_sets_flash_message(): void
    {
        $service = new NotificationService();
        $service->info('Info message');
        
        $this->assertEquals('Info message', Session::get('info'));
    }

    public function test_json_response_method(): void
    {
        $service = new NotificationService();
        
        $response = $service->jsonResponse('Test message', 'success', 200, ['key' => 'value']);
        
        $this->assertEquals(200, $response->status());
        $this->assertEquals([
            'message' => 'Test message',
            'type' => 'success',
            'data' => ['key' => 'value']
        ], $response->getData(true));
    }

    public function test_get_notifications_for_js_method(): void
    {
        $response = $this->withSession([
            'success' => 'Success message',
            'error' => 'Error message'
        ])->get('/test-notification');
        $this->assertEquals('Success message', session('success'));
        $this->assertEquals('Error message', session('error'));
        $response->assertStatus(200);
    }
} 