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
        $this->assertEquals('required|string|max:255', $rules['title']);
        $this->assertEquals('nullable|string', $rules['description']);
        $this->assertEquals('nullable|url', $rules['cover_image']);
        $this->assertEquals('nullable|exists:genres,id', $rules['genre_id']);
        $this->assertEquals('nullable|array', $rules['tracks']);
        $this->assertEquals('exists:tracks,id', $rules['tracks.*']);
    }
}
