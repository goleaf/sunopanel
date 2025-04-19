<?php

namespace Tests\Feature;

use App\Models\Track;
use App\Models\Genre;
use App\Models\Playlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test dashboard displays with statistics.
     */
    public function test_dashboard_displays_with_statistics()
    {
        // Create some test data
        Track::factory()->count(3)->create();
        Genre::factory()->count(2)->create();
        Playlist::factory()->count(1)->create();
        
        // Test dashboard display
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('System Statistics');
    }
    
    /**
     * Test download status endpoint.
     */
    public function test_download_status_endpoint()
    {
        // Skip test as download functionality is now merged into tracks
        $this->markTestSkipped('Download functionality has been removed and merged into tracks');
    }
    
    /**
     * Test simulating download progress.
     */
    public function test_simulate_download_progress()
    {
        // Skip test as download functionality is now merged into tracks
        $this->markTestSkipped('Download functionality has been removed and merged into tracks');
    }
} 