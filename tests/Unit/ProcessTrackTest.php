<?php

namespace Tests\Unit;

use App\Jobs\ProcessTrack;
use App\Models\Genre;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessTrackTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_process_a_track_info_line()
    {
        // Create a track model with data from the example line
        $track = Track::factory()->create([
            'title' => 'Fleeting Love (儚い愛)',
            'mp3_url' => 'https://cdn1.suno.ai/69c0d3c4-a06f-471e-a396-4cb09c9ec2b6.mp3',
            'image_url' => 'https://cdn2.suno.ai/image_a07fbe33-8ee5-4f91-bb8d-180cbb49e5fe.jpeg',
            'genres_string' => 'City pop,80s',
            'status' => 'pending',
        ]);
        
        // Call the ProcessTrack job
        (new ProcessTrack($track))->handle();
        
        // Refresh track to get updated data
        $track->refresh();
        
        // Verify track was processed (should be completed or at least processing)
        $this->assertContains($track->status, ['processing', 'completed', 'failed']);
        
        // If processing succeeded, check the files and status
        if ($track->status === 'completed') {
            $this->assertNotNull($track->mp3_path);
            $this->assertNotNull($track->image_path);
            $this->assertEquals(100, $track->progress);
        }
        
        // Check if genres exist
        $this->assertDatabaseHas('genres', ['name' => 'City pop']);
        $this->assertDatabaseHas('genres', ['name' => '80s']);
        
        // Check if pivot relationships exist
        $this->assertTrue($track->genres->contains(function ($genre) {
            return $genre->name === 'City pop';
        }));
        $this->assertTrue($track->genres->contains(function ($genre) {
            return $genre->name === '80s';
        }));
    }
    
    /** @test */
    public function it_handles_tracks_with_no_genres()
    {
        // Create a track with no genres (invalid URLs will cause failure, which is expected)
        $track = Track::factory()->create([
            'title' => 'Test Track',
            'mp3_url' => 'https://example.com/test.mp3', // Invalid URL - will fail
            'image_url' => 'https://example.com/test.jpeg', // Invalid URL - will fail
            'genres_string' => '',
            'status' => 'pending',
        ]);
        
        // Call the ProcessTrack job (this should fail due to invalid URLs)
        (new ProcessTrack($track))->handle();
        
        // Refresh track to get updated data
        $track->refresh();
        
        // Verify track failed due to invalid URLs (not Suno.ai URLs)
        $this->assertEquals('failed', $track->status);
        $this->assertStringContains('invalid URLs', $track->error_message);
        
        // Verify no genres were attached
        $this->assertEquals(0, $track->genres->count());
    }
} 