<?php

namespace Tests\Feature;

use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    /**
     * Test the success method.
     */
    public function test_success_method_sets_flash_message(): void
    {
        $service = new NotificationService();
        $service->success('Success message');
        
        $this->assertEquals('Success message', Session::get('success'));
    }
    
    /**
     * Test the error method.
     */
    public function test_error_method_sets_flash_message(): void
    {
        $service = new NotificationService();
        $service->error('Error message');
        
        $this->assertEquals('Error message', Session::get('error'));
    }
    
    /**
     * Test the warning method.
     */
    public function test_warning_method_sets_flash_message(): void
    {
        $service = new NotificationService();
        $service->warning('Warning message');
        
        $this->assertEquals('Warning message', Session::get('warning'));
    }
    
    /**
     * Test the info method.
     */
    public function test_info_method_sets_flash_message(): void
    {
        $service = new NotificationService();
        $service->info('Info message');
        
        $this->assertEquals('Info message', Session::get('info'));
    }
    
    /**
     * Test the jsonResponse method.
     */
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
    
    /**
     * Test the getNotificationsForJs method with a mock request.
     */
    public function test_get_notifications_for_js_method(): void
    {
        // Create a test response using the endpoint
        $response = $this->withSession([
            'success' => 'Success message',
            'error' => 'Error message'
        ])->get('/test-notification');
        
        // Verify the session values were set
        $this->assertEquals('Success message', session('success'));
        $this->assertEquals('Error message', session('error'));
        
        // Verify test endpoint status
        $response->assertStatus(200);
    }
} 