<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    
    
    public function test_dashboard_page_loads(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertSee('Welcome to SunoPanel');
    }

    
    
    public function test_dashboard_displays_system_stats(): void {
        Track::factory()->count(3)->create();
        Genre::factory()->count(2)->create();
        Playlist::factory()->count(1)->create();

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('System Statistics', false);
        $response->assertSee('Total Tracks');
        $response->assertSee('Genres');
        $response->assertSee('Playlists');
        $response->assertSee('Total Duration');
    }

    
    
    public function test_system_stats_api_endpoint(): void {
        Track::factory()->count(3)->create();
        Genre::factory()->count(2)->create();
        Playlist::factory()->count(1)->create();

        $response = $this->getJson('/system-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'tracks',
                'genres',
                'playlists',
                'storage',
                'totalDuration',
            ]);
    }
}
