<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\TrackStoreRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Http\Request;

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
    public function testRulesWithoutBulkTracks(): void
    {
        // Create a new request without bulk_tracks
        $request = new TrackStoreRequest();
        $request->replace(['title' => 'Test Track']);
        
        $rules = $request->rules();
        
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('audio_url', $rules);
        $this->assertArrayHasKey('image_url', $rules);
        $this->assertArrayHasKey('duration', $rules);
        $this->assertArrayHasKey('genres', $rules);
        $this->assertArrayHasKey('genre_ids', $rules);
        $this->assertArrayHasKey('genre_ids.*', $rules);
        $this->assertArrayHasKey('playlists', $rules);
        $this->assertArrayHasKey('playlists.*', $rules);
        $this->assertArrayHasKey('bulk_tracks', $rules);
        
        // Check title rules
        $this->assertContains('required', $rules['title']);
        $this->assertContains('string', $rules['title']);
        $this->assertContains('max:255', $rules['title']);
        $this->assertContains('unique:tracks', $rules['title']);
        
        // Check audio_url rules
        $this->assertContains('required', $rules['audio_url']);
        $this->assertContains('url', $rules['audio_url']);
        
        // Check image_url rules
        $this->assertContains('required', $rules['image_url']);
        $this->assertContains('url', $rules['image_url']);
        
        // Check duration rules
        $this->assertContains('nullable', $rules['duration']);
        $this->assertContains('string', $rules['duration']);
        $this->assertContains('max:10', $rules['duration']);
        
        // Check genres rules
        $this->assertContains('nullable', $rules['genres']);
        $this->assertContains('required_without:genre_ids', $rules['genres']);
        $this->assertContains('string', $rules['genres']);
        
        // Check genre_ids rules
        $this->assertContains('nullable', $rules['genre_ids']);
        $this->assertContains('required_without:genres', $rules['genre_ids']);
        $this->assertContains('array', $rules['genre_ids']);
        
        // Check genre_ids.* rules
        $this->assertContains('exists:genres,id', $rules['genre_ids.*']);
        
        // Check playlists rules
        $this->assertContains('nullable', $rules['playlists']);
        $this->assertContains('array', $rules['playlists']);
        
        // Check playlists.* rules
        $this->assertContains('exists:playlists,id', $rules['playlists.*']);
        
        // Check bulk_tracks rules
        $this->assertContains('nullable', $rules['bulk_tracks']);
        $this->assertContains('string', $rules['bulk_tracks']);
    }
    
    #[Test]
    public function testRulesWithBulkTracks(): void
    {
        // Create a new request with bulk_tracks
        $request = new TrackStoreRequest();
        $request->replace(['bulk_tracks' => 'Track 1|URL1|URL2']);
        
        $rules = $request->rules();
        
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('bulk_tracks', $rules);
        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('audio_url', $rules);
        $this->assertArrayHasKey('image_url', $rules);
        $this->assertArrayHasKey('duration', $rules);
        $this->assertArrayHasKey('genres', $rules);
        $this->assertArrayHasKey('genre_ids', $rules);
        $this->assertArrayHasKey('genre_ids.*', $rules);
        $this->assertArrayHasKey('playlists', $rules);
        $this->assertArrayHasKey('playlists.*', $rules);
        
        // Check bulk_tracks rules
        $this->assertContains('required', $rules['bulk_tracks']);
        $this->assertContains('string', $rules['bulk_tracks']);
        
        // Check title rules - now nullable when bulk_tracks is provided
        $this->assertContains('nullable', $rules['title']);
        $this->assertContains('string', $rules['title']);
        $this->assertContains('max:255', $rules['title']);
        
        // Check audio_url rules - now nullable when bulk_tracks is provided
        $this->assertContains('nullable', $rules['audio_url']);
        $this->assertContains('url', $rules['audio_url']);
        
        // Check image_url rules - now nullable when bulk_tracks is provided
        $this->assertContains('nullable', $rules['image_url']);
        $this->assertContains('url', $rules['image_url']);
    }

    #[Test]
    public function testMessages(): void
    {
        $messages = $this->request->messages();
        
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('title.required', $messages);
        $this->assertArrayHasKey('title.unique', $messages);
        $this->assertArrayHasKey('audio_url.required', $messages);
        $this->assertArrayHasKey('audio_url.url', $messages);
        $this->assertArrayHasKey('image_url.required', $messages);
        $this->assertArrayHasKey('image_url.url', $messages);
        $this->assertArrayHasKey('genres.required_without', $messages);
        $this->assertArrayHasKey('genre_ids.required_without', $messages);
        
        $this->assertEquals('The track title is required.', $messages['title.required']);
        $this->assertEquals('A track with this title already exists.', $messages['title.unique']);
        $this->assertEquals('The audio URL is required.', $messages['audio_url.required']);
        $this->assertEquals('The audio URL must be a valid URL.', $messages['audio_url.url']);
        $this->assertEquals('The image URL is required.', $messages['image_url.required']);
        $this->assertEquals('The image URL must be a valid URL.', $messages['image_url.url']);
        $this->assertEquals('Either genres or genre IDs must be provided.', $messages['genres.required_without']);
        $this->assertEquals('Either genres or genre IDs must be provided.', $messages['genre_ids.required_without']);
    }
}
