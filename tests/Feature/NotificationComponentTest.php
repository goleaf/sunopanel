<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationComponentTest extends TestCase
{
    /**
     * Test that the notification component renders correctly.
     */
    public function test_notification_component_renders(): void
    {
        $view = $this->view('components.notification', [
            'type' => 'success',
            'message' => 'Test message',
            'id' => 'test-notification'
        ]);

        $view->assertSee('Test message');
        $view->assertSee('test-notification');
        $view->assertSee('notification notification-success');
    }

    /**
     * Test that the notification component renders with different types.
     */
    public function test_notification_types(): void
    {
        // Test success type
        $view = $this->view('components.notification', [
            'type' => 'success',
            'message' => 'Success message'
        ]);
        $view->assertSee('notification-success');
        $view->assertSee('Success message');

        // Test error type
        $view = $this->view('components.notification', [
            'type' => 'error',
            'message' => 'Error message'
        ]);
        $view->assertSee('notification-error');
        $view->assertSee('Error message');

        // Test warning type
        $view = $this->view('components.notification', [
            'type' => 'warning',
            'message' => 'Warning message'
        ]);
        $view->assertSee('notification-warning');
        $view->assertSee('Warning message');

        // Test info type
        $view = $this->view('components.notification', [
            'type' => 'info',
            'message' => 'Info message'
        ]);
        $view->assertSee('notification-info');
        $view->assertSee('Info message');
    }

    /**
     * Test that the notification component renders without a message.
     */
    public function test_notification_without_message(): void
    {
        $view = $this->view('components.notification', [
            'type' => 'info'
        ]);
        
        $view->assertSee('notification notification-info');
    }

    /**
     * Test that the notification component can be non-dismissable.
     */
    public function test_notification_non_dismissable(): void
    {
        $view = $this->view('components.notification', [
            'type' => 'info',
            'message' => 'Info message',
            'dismissable' => false
        ]);
        
        $view->assertDontSee('close-button');
    }
} 