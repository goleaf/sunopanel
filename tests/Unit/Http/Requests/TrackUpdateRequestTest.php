<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\TrackUpdateRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Routing\Route;

class TrackUpdateRequestTest extends TestCase
{
    private TrackUpdateRequest $request;
    private \stdClass $trackObj;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new TrackUpdateRequest();
        $this->trackObj = new \stdClass();
        $this->trackObj->id = 1;
        $this->request->setRouteResolver(function () {
            $route = $this->createMock(Route::class);
            $route->method('parameter')
                ->with('track')
                ->willReturn($this->trackObj);
            
            return $route;
        });
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
        $this->assertArrayHasKey('audio_url', $rules);
        $this->assertArrayHasKey('image_url', $rules);
        $this->assertArrayHasKey('duration', $rules);
        $this->assertArrayHasKey('genres', $rules);
        $this->assertArrayHasKey('genre_ids', $rules);
        $this->assertArrayHasKey('genre_ids.*', $rules);
        $this->assertArrayHasKey('playlists', $rules);
        $this->assertArrayHasKey('playlists.*', $rules);
        $this->assertIsArray($rules['title']);
        $this->assertContains('required', $rules['title']);
        $this->assertContains('string', $rules['title']);
        $this->assertContains('max:255', $rules['title']);
        $hasUniqueRule = false;
        foreach ($rules['title'] as $rule) {
            if (is_object($rule)) {
                $hasUniqueRule = true;
                break;
            }
        }
        $this->assertTrue($hasUniqueRule, 'The title unique rule is missing');
        $this->assertContains('required', $rules['audio_url']);
        $this->assertContains('url', $rules['audio_url']);
        $this->assertContains('required', $rules['image_url']);
        $this->assertContains('url', $rules['image_url']);
        $this->assertContains('nullable', $rules['duration']);
        $this->assertContains('string', $rules['duration']);
        $this->assertContains('max:10', $rules['duration']);
        $this->assertContains('nullable', $rules['genres']);
        $this->assertContains('required_without:genre_ids', $rules['genres']);
        $this->assertContains('string', $rules['genres']);
        $this->assertContains('nullable', $rules['genre_ids']);
        $this->assertContains('required_without:genres', $rules['genre_ids']);
        $this->assertContains('array', $rules['genre_ids']);
        $this->assertContains('exists:genres,id', $rules['genre_ids.*']);
        $this->assertContains('nullable', $rules['playlists']);
        $this->assertContains('array', $rules['playlists']);
        $this->assertContains('exists:playlists,id', $rules['playlists.*']);
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
