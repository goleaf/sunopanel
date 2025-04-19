<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\GenreUpdateRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class GenreUpdateRequestTest extends TestCase
{
    private GenreUpdateRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new GenreUpdateRequest();
        
        // Mock the route to provide a genre parameter
        $route = new Route('PUT', '/genres/{genre}', []);
        $route->parameters = ['genre' => 1];
        $this->request->setRouteResolver(function () use ($route) {
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
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('cover_image', $rules);
        
        // Check name validation rules
        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:255', $rules['name']);
        
        // Check for the presence of a Rule object in the name rules
        $hasUniqueRule = false;
        foreach ($rules['name'] as $rule) {
            if (is_object($rule) && get_class($rule) === 'Illuminate\Validation\Rules\Unique') {
                $hasUniqueRule = true;
                break;
            }
        }
        $this->assertTrue($hasUniqueRule, 'The unique rule is missing');
        
        // Check description validation rules
        $this->assertContains('nullable', $rules['description']);
        $this->assertContains('string', $rules['description']);
        
        // Check cover_image validation rules
        $this->assertContains('nullable', $rules['cover_image']);
        $this->assertContains('url', $rules['cover_image']);
    }
}
