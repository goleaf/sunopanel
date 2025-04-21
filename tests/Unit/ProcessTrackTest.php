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
        // Example line from the provided list
        $line = 'Fleeting Love (儚い愛).mp3|https://cdn1.suno.ai/69c0d3c4-a06f-471e-a396-4cb09c9ec2b6.mp3|https://cdn2.suno.ai/image_a07fbe33-8ee5-4f91-bb8d-180cbb49e5fe.jpeg|City pop,80s';
        
        // Call the ProcessTrack job
        (new ProcessTrack($line))->handle();
        
        // Verify track was created
        $this->assertDatabaseHas('tracks', [
            'title' => 'Fleeting Love (儚い愛)',
            'audio_url' => 'https://cdn1.suno.ai/69c0d3c4-a06f-471e-a396-4cb09c9ec2b6.mp3',
            'image_url' => 'https://cdn2.suno.ai/image_a07fbe33-8ee5-4f91-bb8d-180cbb49e5fe.jpeg',
        ]);
        
        // Verify genres were created and attached
        $track = Track::where('title', 'Fleeting Love (儚い愛)')->first();
        $this->assertNotNull($track);
        
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
        // Example line with no genres
        $line = 'Test Track.mp3|https://example.com/test.mp3|https://example.com/test.jpeg|';
        
        // Call the ProcessTrack job
        (new ProcessTrack($line))->handle();
        
        // Verify track was created
        $this->assertDatabaseHas('tracks', [
            'title' => 'Test Track',
            'audio_url' => 'https://example.com/test.mp3',
            'image_url' => 'https://example.com/test.jpeg',
        ]);
        
        // Verify no genres were attached
        $track = Track::where('title', 'Test Track')->first();
        $this->assertNotNull($track);
        $this->assertEquals(0, $track->genres->count());
    }
} 