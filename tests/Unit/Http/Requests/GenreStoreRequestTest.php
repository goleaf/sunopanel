<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\GenreStoreRequest;
use Illuminate\Validation\Rule;
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
    public function testAuthorize(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    #[Test]
    public function testRules(): void
    {
        $rules = $this->request->rules();
        
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('cover_image', $rules);
        
        // Check name validation rules
        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:255', $rules['name']);
        $this->assertContains('unique:genres,name', $rules['name']);
        
        // Check description validation rules
        $this->assertContains('nullable', $rules['description']);
        $this->assertContains('string', $rules['description']);
        
        // Check cover_image validation rules
        $this->assertContains('nullable', $rules['cover_image']);
        $this->assertContains('url', $rules['cover_image']);
    }
}
