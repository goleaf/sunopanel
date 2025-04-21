<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\PlaylistListRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlaylistListRequestTest extends TestCase
{
    private PlaylistListRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new PlaylistListRequest();
    }
    
    #[Test]
    public function test_Authorize(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    #[Test]
    public function test_Rules(): void
    {
        $rules = $this->request->rules();
        
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('search', $rules);
        $this->assertArrayHasKey('genreFilter', $rules);
        $this->assertArrayHasKey('perPage', $rules);
        $this->assertArrayHasKey('sortField', $rules);
        $this->assertArrayHasKey('direction', $rules);
        $this->assertArrayHasKey('playlistId', $rules);
        
        // Check specific rule values
        $this->assertStringContainsString('nullable', $rules['search']);
        $this->assertStringContainsString('string', $rules['search']);
        $this->assertStringContainsString('max:255', $rules['search']);
        
        $this->assertStringContainsString('nullable', $rules['genreFilter']);
        $this->assertStringContainsString('exists:genres,id', $rules['genreFilter']);
        
        $this->assertStringContainsString('integer', $rules['perPage']);
        $this->assertStringContainsString('in:5,10,15,25,50', $rules['perPage']);
        
        $this->assertStringContainsString('string', $rules['sortField']);
        $this->assertStringContainsString('in:title,created_at,updated_at,track_count', $rules['sortField']);
        
        $this->assertStringContainsString('string', $rules['direction']);
        $this->assertStringContainsString('in:asc,desc', $rules['direction']);
        
        $this->assertStringContainsString('nullable', $rules['playlistId']);
        $this->assertStringContainsString('exists:playlists,id', $rules['playlistId']);
    }

    #[Test]
    public function test_Messages(): void
    {
        $messages = $this->request->messages();
        
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('sortField.in', $messages);
        $this->assertArrayHasKey('direction.in', $messages);
        $this->assertArrayHasKey('perPage.in', $messages);
        $this->assertArrayHasKey('genreFilter.exists', $messages);
        $this->assertArrayHasKey('playlistId.exists', $messages);
        
        // Verify specific messages
        $this->assertEquals('The sort field is invalid.', $messages['sortField.in']);
        $this->assertEquals('The sort direction is invalid.', $messages['direction.in']);
        $this->assertEquals('The page size is invalid.', $messages['perPage.in']);
        $this->assertEquals('The selected genre does not exist.', $messages['genreFilter.exists']);
        $this->assertEquals('The selected playlist does not exist.', $messages['playlistId.exists']);
    }
} 