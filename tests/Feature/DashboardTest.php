<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_with_statistics()
    {
        Track::factory()->count(3)->create();
        Genre::factory()->count(2)->create();
        Playlist::factory()->count(1)->create();
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('System Statistics');
    }

    public function test_download_status_endpoint()
    {
        $this->markTestSkipped('Download functionality has been removed and merged into tracks');
    }

    public function test_simulate_download_progress()
    {
        $this->markTestSkipped('Download functionality has been removed and merged into tracks');
    }
}
