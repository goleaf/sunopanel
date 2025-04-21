<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiddlewareIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    public function testCsrfProtectionCanBeBypassedInTests(): void
    {
        // Test we can bypass CSRF in tests
        $response = $this->withoutMiddleware()
            ->post('/tracks', [
                'name' => 'Test Track'
            ]);
        
        // Should not get a 419 (CSRF token mismatch) when middleware is disabled
        $this->assertNotEquals(419, $response->getStatusCode());
    }
    
    public function testOfflineRouteWorks(): void
    {
        // Test the offline route used by service worker
        $response = $this->get('/offline');
        $this->assertTrue($response->isOk());
    }
} 