<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\BulkTrackRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BulkTrackRequestTest extends TestCase
{
    private BulkTrackRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new BulkTrackRequest();
    }
    
    #[Test]
    public function testAuthorize(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    #[Test]
    public function testRules(): void
    {
        $rules = $this->request->rules();
        
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('bulk_tracks', $rules);
        
        // Check bulk_tracks rules
        $this->assertContains('required', $rules['bulk_tracks']);
        $this->assertContains('string', $rules['bulk_tracks']);
        $this->assertContains('min:5', $rules['bulk_tracks']);
    }

    #[Test]
    public function testMessages(): void
    {
        $messages = $this->request->messages();
        
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('bulk_tracks.required', $messages);
        $this->assertArrayHasKey('bulk_tracks.min', $messages);
        
        $this->assertEquals('Please provide track data for bulk upload.', $messages['bulk_tracks.required']);
        $this->assertEquals('The bulk tracks data is too short. Please provide valid track data.', $messages['bulk_tracks.min']);
    }

    #[Test]
    public function testWithValidator(): void
    {
        // For this test, we need to use a Laravel application test to check the actual validator
        // Here we can test if the method exists and is callable
        $this->assertTrue(method_exists($this->request, 'withValidator'));
        
        // Additional test for the validator could be implemented in a feature test
        // where we can actually validate a complete request
        $reflectionMethod = new \ReflectionMethod(BulkTrackRequest::class, 'withValidator');
        $this->assertTrue($reflectionMethod->isPublic());
    }
}
