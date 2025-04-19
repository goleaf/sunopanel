<?php

namespace Tests\Feature;

use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_track_store_validation()
    {
        // Test missing required fields
        $response = $this->post(route('tracks.store'), []);
        $response->assertSessionHasErrors(['title', 'audio_url', 'image_url', 'genres']);

        // Test valid data
        $validData = [
            'title' => 'Test Track',
            'audio_url' => 'https://example.com/audio.mp3',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => 'Bubblegum bass',
            'duration' => '3:30'
        ];
        
        $response = $this->post(route('tracks.store'), $validData);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('tracks.index'));
        
        $this->assertDatabaseHas('tracks', [
            'title' => 'Test Track',
            'audio_url' => 'https://example.com/audio.mp3',
            'image_url' => 'https://example.com/image.jpg',
            'duration' => '3:30'
        ]);
    }

    public function test_track_update_validation()
    {
        // Create a track
        $track = Track::create([
            'title' => 'Original Track',
            'audio_url' => 'https://example.com/original-audio.mp3',
            'image_url' => 'https://example.com/original-image.jpg',
            'unique_id' => Track::generateUniqueId('Original Track'),
            'duration' => '3:00'
        ]);

        // Test missing required fields
        $response = $this->put(route('tracks.update', $track->id), []);
        $response->assertSessionHasErrors(['title', 'audio_url', 'image_url', 'genres']);

        // Test valid data
        $validData = [
            'title' => 'Updated Track',
            'audio_url' => 'https://example.com/updated-audio.mp3',
            'image_url' => 'https://example.com/updated-image.jpg',
            'genres' => 'Bubblegum bass, Chillwave',
            'duration' => '4:15'
        ];
        
        $response = $this->put(route('tracks.update', $track->id), $validData);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('tracks.index'));
        
        $this->assertDatabaseHas('tracks', [
            'id' => $track->id,
            'title' => 'Updated Track',
            'audio_url' => 'https://example.com/updated-audio.mp3',
            'image_url' => 'https://example.com/updated-image.jpg',
            'duration' => '4:15'
        ]);
    }

    public function test_bulk_track_upload()
    {
        $bulkData = "Test Track 1|https://example.com/audio1.mp3|https://example.com/image1.jpg|Bubblegum bass\n";
        $bulkData .= "Test Track 2|https://example.com/audio2.mp3|https://example.com/image2.jpg|Chillwave";
        
        $response = $this->post(route('tracks.store'), [
            'bulk_tracks' => $bulkData,
            // Add these required fields to pass validation in the TrackStoreRequest
            'title' => 'Dummy Name',
            'audio_url' => 'https://example.com/dummy.mp3',
            'image_url' => 'https://example.com/dummy.jpg',
            'genres' => 'Dummy Genre'
        ]);
        
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('tracks.index'));
        
        $this->assertDatabaseHas('tracks', [
            'title' => 'Test Track 1',
            'audio_url' => 'https://example.com/audio1.mp3',
            'image_url' => 'https://example.com/image1.jpg'
        ]);
        
        $this->assertDatabaseHas('tracks', [
            'title' => 'Test Track 2',
            'audio_url' => 'https://example.com/audio2.mp3',
            'image_url' => 'https://example.com/image2.jpg'
        ]);
        
        // Check if genres were created and attached properly
        $track1 = Track::where('title', 'Test Track 1')->first();
        $this->assertTrue($track1->genres()->where('name', 'Bubblegum bass')->exists());
        
        $track2 = Track::where('title', 'Test Track 2')->first();
        $this->assertTrue($track2->genres()->where('name', 'Chillwave')->exists());
    }
} 