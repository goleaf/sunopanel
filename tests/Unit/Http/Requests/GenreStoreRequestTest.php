<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\GenreStoreRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GenreStoreRequestTest extends TestCase
{
    private GenreStoreRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new GenreStoreRequest();
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
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('cover_image', $rules);
        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:255', $rules['name']);
        $this->assertContains('unique:genres,name', $rules['name']);
        $this->assertContains('nullable', $rules['description']);
        $this->assertContains('string', $rules['description']);
        $this->assertContains('nullable', $rules['cover_image']);
        $this->assertContains('url', $rules['cover_image']);
    }
}
