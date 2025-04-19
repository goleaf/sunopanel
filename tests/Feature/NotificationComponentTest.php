<?php

namespace Tests\Feature;

use Tests\TestCase;

class NotificationComponentTest extends TestCase
{

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

    public function test_notification_types(): void
    {
        $view = $this->view('components.notification', [
            'type' => 'success',
            'message' => 'Success message'
        ]);
        $view->assertSee('notification-success');
        $view->assertSee('Success message');
        $view = $this->view('components.notification', [
            'type' => 'error',
            'message' => 'Error message'
        ]);
        $view->assertSee('notification-error');
        $view->assertSee('Error message');
        $view = $this->view('components.notification', [
            'type' => 'warning',
            'message' => 'Warning message'
        ]);
        $view->assertSee('notification-warning');
        $view->assertSee('Warning message');
        $view = $this->view('components.notification', [
            'type' => 'info',
            'message' => 'Info message'
        ]);
        $view->assertSee('notification-info');
        $view->assertSee('Info message');
    }

    public function test_notification_without_message(): void
    {
        $view = $this->view('components.notification', [
            'type' => 'info'
        ]);
        
        $view->assertSee('notification notification-info');
    }

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