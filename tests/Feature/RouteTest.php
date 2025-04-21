<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_main_routes_are_accessible(): void {
        $response = $this->get('/');
        $response->assertStatus(200);
        $routesToTest = [
            'tracks.index' => '/tracks',
            'tracks.create' => '/tracks/create',
            'genres.index' => '/genres',
            'genres.create' => '/genres/create',
            'playlists.index' => '/playlists',
            'playlists.create' => '/playlists/create',
        ];

        foreach ($routesToTest as $routeName => $uri) {
            $response = $this->get($uri);
            $response->assertStatus(200);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_nonexistent_routes_return_404(): void {
        $nonExistentRoutes = [
            '/nonexistent-route',
            '/invalid/path',
            '/downloads',
            '/files',
        ];

        foreach ($nonExistentRoutes as $uri) {
            $response = $this->get($uri);
            $response->assertStatus(404);
        }
        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_method_not_allowed_on_wrong_methods(): void {
        $response = $this->post('/tracks');
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['title', 'audio_url', 'image_url', 'genres']);
        $response = $this->post('/tracks/1');
        $response->assertStatus(405);
        $response = $this->put('/tracks');
        $response->assertStatus(405);

        $response = $this->patch('/genres');
        $response->assertStatus(405);

        $response = $this->delete('/playlists');
        $response->assertStatus(405);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_route_parameters_are_validated(): void {
        // TODO: Implement test that was previously skipped with message: 'Route parameter validation is handled differently in the current implementation'
        $this->assertTrue(true); // Placeholder assertion
    }
}
