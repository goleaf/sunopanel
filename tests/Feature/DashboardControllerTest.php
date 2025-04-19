<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Track;
use App\Models\Genre;
use App\Models\Playlist;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the dashboard page loads correctly.
     */
    public function test_dashboard_page_loads(): void
    {
        $response = $this->get('/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertSee('Welcome to SunoPanel');
    }
    
    /**
     * Test dashboard displays system stats.
     */
    public function test_dashboard_displays_system_stats()
    {
        // Create some test data
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
    
    /**
     * Test system stats API endpoint.
     */
    public function test_system_stats_api_endpoint()
    {
        // Create some test data
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
                    'totalDuration'
                ]);
    }
} 