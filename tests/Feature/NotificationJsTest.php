<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationJsTest extends TestCase
{
    /**
     * Test that the notification component script is properly loaded.
     */
    public function test_notification_scripts_are_loaded(): void
    {
        $response = $this->get('/test-notification');
        
        // Check HTTP status
        $response->assertStatus(200);
        
        // Check if the notification test page loaded
        $response->assertSee('Notification Component Test');
        
        // Check if notification buttons are present
        $response->assertSee('Success notification message');
        $response->assertSee('Error notification message');
        $response->assertSee('showNotification');
    }

    /**
     * Test that the session flash messages are properly handled.
     */
    public function test_session_flash_messages(): void
    {
        // Test success message
        $response = $this->withSession(['success' => 'Operation completed successfully'])
                         ->get('/test-notification');
        
        $response->assertStatus(200);
        
        // Test error message
        $response = $this->withSession(['error' => 'An error occurred'])
                         ->get('/test-notification');
        
        $response->assertStatus(200);
        
        // Test warning message
        $response = $this->withSession(['warning' => 'This is a warning'])
                         ->get('/test-notification');
        
        $response->assertStatus(200);
        
        // Test info message
        $response = $this->withSession(['info' => 'For your information'])
                         ->get('/test-notification');
        
        $response->assertStatus(200);
    }
    
    /**
     * Test the notification test page itself.
     */
    public function test_notification_test_page_loads(): void
    {
        $response = $this->get('/test-notification');
        
        $response->assertStatus(200);
        $response->assertSee('Notification Component Test');
        $response->assertSee('Flash Messages');
        $response->assertSee('JavaScript Notifications');
        $response->assertSee('Custom Notifications');
    }
} 