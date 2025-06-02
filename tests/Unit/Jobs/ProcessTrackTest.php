<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessTrack;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ProcessTrackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function testProcessTrackDownloadsFilesAndCreatesMP4(): void
    {
        // Sample track data (using the first one from your list)
        $trackData = [
            'title' => 'Fleeting Love',
            'mp3_url' => 'https://cdn1.suno.ai/69c0d3c4-a06f-471e-a396-4cb09c9ec2b6.mp3',
            'image_url' => 'https://cdn2.suno.ai/image_a07fbe33-8ee5-4f91-bb8d-180cbb49e5fe.jpeg',
            'status' => 'pending',
            'progress' => 0
        ];

        // Create track
        $track = Track::create($trackData);

        // Mock HTTP responses for file downloads
        Http::fake([
            $trackData['mp3_url'] => Http::response('fake mp3 content', 200),
            $trackData['image_url'] => Http::response('fake image content', 200),
        ]);

        // Create a partial mock of the ProcessTrack job
        $job = Mockery::mock(ProcessTrack::class, [$track])->makePartial();
        
        // Mock the createMP4 method to avoid actual FFmpeg calls
        $job->shouldReceive('createMP4')
            ->once()
            ->andReturn('tracks/test_output.mp4');
            
        // Run the job
        $job->handle();

        // Refresh track from database
        $track->refresh();

        // Assert track has been processed successfully
        $this->assertEquals('completed', $track->status);
        $this->assertEquals(100, $track->progress);
        $this->assertNotNull($track->mp3_path);
        $this->assertNotNull($track->image_path);
        $this->assertEquals('tracks/test_output.mp4', $track->mp4_path);

        // Assert files were "stored" in our fake storage
        Storage::disk('public')->assertExists($track->mp3_path);
        Storage::disk('public')->assertExists($track->image_path);
    }

    public function testProcessTrackHandlesErrors(): void
    {
        // Sample track with invalid URLs (non-Suno.ai URLs will be rejected)
        $trackData = [
            'title' => 'Error Test Track',
            'mp3_url' => 'https://example.com/bad-url.mp3',
            'image_url' => 'https://example.com/bad-image.jpg',
            'status' => 'pending',
            'progress' => 0
        ];

        // Create track
        $track = Track::create($trackData);

        // Process should catch the exception due to invalid URLs
        $job = new ProcessTrack($track);
        
        try {
            $job->handle();
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Expected exception for invalid URLs
            $this->assertStringContainsString('invalid URLs', $e->getMessage());
        }

        // Refresh track from database
        $track->refresh();

        // Assert track has failed status
        $this->assertEquals('failed', $track->status);
        $this->assertEquals(0, $track->progress);
        $this->assertNotNull($track->error_message);
        $this->assertStringContainsString('invalid URLs', $track->error_message);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
