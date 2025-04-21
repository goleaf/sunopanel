<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\TrackListRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrackListRequestTest extends TestCase
{
    private TrackListRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new TrackListRequest();
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
        $this->assertArrayHasKey('search', $rules);
        $this->assertArrayHasKey('genreFilter', $rules);
        $this->assertArrayHasKey('perPage', $rules);
        $this->assertArrayHasKey('sortField', $rules);
        $this->assertArrayHasKey('direction', $rules);
        $this->assertArrayHasKey('trackIdToDelete', $rules);
        
        // Check specific rule values
        $this->assertStringContainsString('nullable', $rules['search']);
        $this->assertStringContainsString('string', $rules['search']);
        $this->assertStringContainsString('max:255', $rules['search']);
        
        $this->assertStringContainsString('nullable', $rules['genreFilter']);
        $this->assertStringContainsString('exists:genres,id', $rules['genreFilter']);
        
        $this->assertStringContainsString('integer', $rules['perPage']);
        $this->assertStringContainsString('in:5,10,15,25,50', $rules['perPage']);
        
        $this->assertStringContainsString('string', $rules['sortField']);
        $this->assertStringContainsString('in:title,artist,album,created_at,updated_at', $rules['sortField']);
        
        $this->assertStringContainsString('string', $rules['direction']);
        $this->assertStringContainsString('in:asc,desc', $rules['direction']);
        
        $this->assertStringContainsString('nullable', $rules['trackIdToDelete']);
        $this->assertStringContainsString('exists:tracks,id', $rules['trackIdToDelete']);
    }

    #[Test]
    public function testMessages(): void
    {
        $messages = $this->request->messages();
        
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('genreFilter.exists', $messages);
        $this->assertArrayHasKey('perPage.in', $messages);
        $this->assertArrayHasKey('sortField.in', $messages);
        $this->assertArrayHasKey('direction.in', $messages);
        $this->assertArrayHasKey('trackIdToDelete.exists', $messages);
        
        // Verify specific messages
        $this->assertEquals('The selected genre does not exist.', $messages['genreFilter.exists']);
        $this->assertEquals('The selected page size is invalid.', $messages['perPage.in']);
        $this->assertEquals('The sort field is invalid.', $messages['sortField.in']);
        $this->assertEquals('The sort direction is invalid.', $messages['direction.in']);
        $this->assertEquals('The track to delete does not exist.', $messages['trackIdToDelete.exists']);
    }
} 