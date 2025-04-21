<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class NotificationJsTest extends TestCase
{

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_notification_scripts_are_loaded(): void
    {
        $response = $this->get('/test-notification');
        $response->assertStatus(200);
        $response->assertSee('Notification Component Test');
        $response->assertSee('Success notification message');
        $response->assertSee('Error notification message');
        $response->assertSee('showNotification');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_session_flash_messages(): void
    {
        $response = $this->withSession(['success' => 'Operation completed successfully'])
                         ->get('/test-notification');
        
        $response->assertStatus(200);
        $response = $this->withSession(['error' => 'An error occurred'])
                         ->get('/test-notification');
        
        $response->assertStatus(200);
        $response = $this->withSession(['warning' => 'This is a warning'])
                         ->get('/test-notification');
        
        $response->assertStatus(200);
        $response = $this->withSession(['info' => 'For your information'])
                         ->get('/test-notification');
        
        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
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