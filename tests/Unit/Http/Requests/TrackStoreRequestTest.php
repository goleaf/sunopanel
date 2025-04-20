<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\TrackStoreRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrackStoreRequestTest extends TestCase
{
    private TrackStoreRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new TrackStoreRequest();
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
        $this->assertArrayHasKey('artist', $rules);
        $this->assertArrayHasKey('album', $rules);
        $this->assertArrayHasKey('duration', $rules);
        $this->assertArrayHasKey('selectedGenres', $rules);
        $this->assertArrayHasKey('selectedGenres.*', $rules);
        $this->assertArrayHasKey('audio_url', $rules);
        $this->assertArrayHasKey('image_url', $rules);
        $this->assertArrayHasKey('genres', $rules);
        $this->assertArrayHasKey('genre_ids', $rules);
        $this->assertArrayHasKey('genre_ids.*', $rules);
        
        // Check specific rule values
        $this->assertStringContainsString('required', $rules['title']);
        $this->assertStringContainsString('string', $rules['title']);
        $this->assertStringContainsString('max:255', $rules['title']);
        
        $this->assertStringContainsString('nullable', $rules['artist']);
        $this->assertStringContainsString('string', $rules['artist']);
        
        $this->assertStringContainsString('nullable', $rules['album']);
        $this->assertStringContainsString('string', $rules['album']);
        
        $this->assertStringContainsString('nullable', $rules['duration']);
        $this->assertStringContainsString('string', $rules['duration']);
        
        $this->assertStringContainsString('nullable', $rules['selectedGenres']);
        $this->assertStringContainsString('array', $rules['selectedGenres']);
        
        $this->assertStringContainsString('exists:genres,id', $rules['selectedGenres.*']);
        
        $this->assertStringContainsString('required', $rules['audio_url']);
        $this->assertStringContainsString('string', $rules['audio_url']);
        
        $this->assertStringContainsString('nullable', $rules['image_url']);
        $this->assertStringContainsString('string', $rules['image_url']);
        
        $this->assertStringContainsString('nullable', $rules['genres']);
        $this->assertStringContainsString('string', $rules['genres']);
        
        $this->assertStringContainsString('nullable', $rules['genre_ids']);
        $this->assertStringContainsString('array', $rules['genre_ids']);
        
        $this->assertStringContainsString('exists:genres,id', $rules['genre_ids.*']);
    }

    #[Test]
    public function testMessages(): void
    {
        $messages = $this->request->messages();
        
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('title.required', $messages);
        $this->assertArrayHasKey('title.max', $messages);
        $this->assertArrayHasKey('artist.max', $messages);
        $this->assertArrayHasKey('album.max', $messages);
        $this->assertArrayHasKey('duration.max', $messages);
        $this->assertArrayHasKey('audio_url.required', $messages);
        $this->assertArrayHasKey('selectedGenres.*.exists', $messages);
        $this->assertArrayHasKey('genre_ids.*.exists', $messages);
        
        $this->assertEquals('The track title is required.', $messages['title.required']);
        $this->assertEquals('The track title cannot exceed 255 characters.', $messages['title.max']);
        $this->assertEquals('The artist name cannot exceed 255 characters.', $messages['artist.max']);
        $this->assertEquals('The album name cannot exceed 255 characters.', $messages['album.max']);
        $this->assertEquals('The duration format is invalid.', $messages['duration.max']);
        $this->assertEquals('The audio URL is required.', $messages['audio_url.required']);
        $this->assertEquals('One or more selected genres do not exist in our system.', $messages['selectedGenres.*.exists']);
        $this->assertEquals('One or more selected genres do not exist in our system.', $messages['genre_ids.*.exists']);
    }
}
