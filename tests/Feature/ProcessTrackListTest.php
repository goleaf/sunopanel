<?php

namespace Tests\Feature;

use App\Jobs\ProcessTrack;
use App\Models\Genre;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessTrackListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test processing a list of tracks.
     */
    public function test_process_track_list(): void
    {
        // Mock the queue so jobs are not actually processed
        Queue::fake();

        // Create genres that might be used
        $this->createGenres(['City pop', '80s', 'lofi', 'R&B', 'synthwave', 'vaporwave', 
            'psybient', 'acid rock', 'New age funk', 'brass band', 'disco', 'japanese', 
            'Nostalgic city-pop', 'AOR', 'dance', 'pop', 'lo-fi jazz']);

        // Sample track list
        $trackList = [
            "Fleeting Love (儚い愛).mp3|https://cdn1.suno.ai/69c0d3c4-a06f-471e-a396-4cb09c9ec2b6.mp3|https://cdn2.suno.ai/image_a07fbe33-8ee5-4f91-bb8d-180cbb49e5fe.jpeg|City pop,80s",
            "Palakpakan.mp3|https://cdn1.suno.ai/9a00dc20-9640-4150-9804-d8a179ce860c.mp3|https://cdn2.suno.ai/image_9a00dc20-9640-4150-9804-d8a179ce860c.jpeg|city pop",
            "ジャカジャカ.mp3|https://cdn1.suno.ai/837cd038-c104-405b-b1d5-bafa924a277f.mp3|https://cdn2.suno.ai/image_837cd038-c104-405b-b1d5-bafa924a277f.jpeg|city pop",
            "無言の告別.mp3|https://cdn1.suno.ai/86c03eaa-facb-487c-96d5-015a0d3fcc72.mp3|https://cdn2.suno.ai/image_463417b7-1282-4083-a681-c11848872ba1.jpeg|lofi,City pop,R&B"
        ];

        // Send track list to controller
        $response = $this->post('/process', [
            'tracks_input' => implode("\n", $trackList)
        ]);

        // Assert success response
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Assert tracks were created
        $this->assertEquals(4, Track::count());

        // Assert track details
        $tracks = Track::all();
        
        // First track
        $track1 = $tracks->where('title', 'Fleeting Love (儚い愛)')->first();
        $this->assertNotNull($track1);
        $this->assertEquals('https://cdn1.suno.ai/69c0d3c4-a06f-471e-a396-4cb09c9ec2b6.mp3', $track1->mp3_url);
        $this->assertEquals('https://cdn2.suno.ai/image_a07fbe33-8ee5-4f91-bb8d-180cbb49e5fe.jpeg', $track1->image_url);
        $this->assertEquals('City pop,80s', $track1->genres_string);
        $this->assertEquals('pending', $track1->status);
        
        // Check genre associations (genres are attached when ProcessTrack job runs, not during creation)
        // Since we're faking the queue, the job hasn't run yet, so no genres are attached
        $this->assertEquals(0, $track1->genres()->count());
        
        // But the genres_string should contain the genre information
        $this->assertStringContainsString('City pop', $track1->genres_string);
        $this->assertStringContainsString('80s', $track1->genres_string);
        
        // Verify jobs were dispatched
        Queue::assertPushed(ProcessTrack::class, 4);
    }

    /**
     * Create genres from a list of names.
     */
    private function createGenres(array $genreNames): void
    {
        foreach ($genreNames as $name) {
            Genre::create(['name' => $name]);
        }
    }
} 