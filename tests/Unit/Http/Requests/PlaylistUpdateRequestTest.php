<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\PlaylistUpdateRequest;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Routing\Route;

class PlaylistUpdateRequestTest extends TestCase
{
    private PlaylistUpdateRequest $request;
    private \stdClass $playlistObj;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new PlaylistUpdateRequest();
        
        // Setup playlist object
        $this->playlistObj = new \stdClass();
        $this->playlistObj->id = 1;
        
        // Mock the route resolver
        $this->request->setRouteResolver(function () {
            $route = $this->createMock(Route::class);
            $route->method('parameter')
                ->with('playlist')
                ->willReturn($this->playlistObj);
            
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
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('cover_image', $rules);
        $this->assertArrayHasKey('genre_id', $rules);
        $this->assertArrayHasKey('tracks', $rules);
        $this->assertArrayHasKey('tracks.*', $rules);
        
        // Check title rules - this is now an array because of the Rule::unique
        $this->assertIsArray($rules['title']);
        $this->assertContains('required', $rules['title']);
        $this->assertContains('string', $rules['title']);
        $this->assertContains('max:255', $rules['title']);
        
        // Check there's a Rule object in the title rules
        $hasUniqueRule = false;
        foreach ($rules['title'] as $rule) {
            if (is_object($rule)) {
                $hasUniqueRule = true;
                break;
            }
        }
        $this->assertTrue($hasUniqueRule, 'The title unique rule is missing');
        
        // Check the string-formatted rules
        $this->assertEquals('nullable|string', $rules['description']);
        $this->assertEquals('nullable|url', $rules['cover_image']);
        $this->assertEquals('nullable|exists:genres,id', $rules['genre_id']);
        $this->assertEquals('nullable|array', $rules['tracks']);
        $this->assertEquals('exists:tracks,id', $rules['tracks.*']);
    }
}
