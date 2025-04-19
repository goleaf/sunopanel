<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\GenreDeleteRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenreDeleteRequestTest extends TestCase
{
    private GenreDeleteRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new GenreDeleteRequest();
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
        $this->assertArrayHasKey('id', $rules);
        
        // Check that the rule is a string with the correct format
        $this->assertEquals('sometimes|exists:genres,id', $rules['id']);
        
        // Alternatively, if the rules are defined as an array:
        // $this->assertContains('sometimes', $rules['id']);
        // $this->assertContains('exists:genres,id', $rules['id']);
    }
}
