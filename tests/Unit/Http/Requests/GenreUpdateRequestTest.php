<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\GenreUpdateRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Routing\Route;

class GenreUpdateRequestTest extends TestCase
{
    private GenreUpdateRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new GenreUpdateRequest();
        $route = new Route('PUT', '/genres/{genre}', []);
        $route->parameters = ['genre' => 1];
        $this->request->setRouteResolver(function () use ($route) {
            return $route;
        });
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
        $hasUniqueRule = false;
        foreach ($rules['name'] as $rule) {
            if (is_object($rule) && get_class($rule) === 'Illuminate\Validation\Rules\Unique') {
                $hasUniqueRule = true;
                break;
            }
        }
        $this->assertTrue($hasUniqueRule, 'The unique rule is missing');
        $this->assertContains('nullable', $rules['description']);
        $this->assertContains('string', $rules['description']);
        $this->assertContains('nullable', $rules['cover_image']);
        $this->assertContains('url', $rules['cover_image']);
    }
}
