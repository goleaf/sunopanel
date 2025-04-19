<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\PlaylistStoreRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlaylistStoreRequestTest extends TestCase
{
    private PlaylistStoreRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new PlaylistStoreRequest();
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
        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('cover_image', $rules);
        $this->assertArrayHasKey('genre_id', $rules);
        $this->assertArrayHasKey('tracks', $rules);
        $this->assertArrayHasKey('tracks.*', $rules);
        
        // Check title rules
        $this->assertEquals('required|string|max:255', $rules['title']);
        
        // Check description rules
        $this->assertEquals('nullable|string', $rules['description']);
        
        // Check cover_image rules
        $this->assertEquals('nullable|url', $rules['cover_image']);
        
        // Check genre_id rules
        $this->assertEquals('nullable|exists:genres,id', $rules['genre_id']);
        
        // Check tracks rules
        $this->assertEquals('nullable|array', $rules['tracks']);
        
        // Check tracks.* rules
        $this->assertEquals('exists:tracks,id', $rules['tracks.*']);
    }
}
