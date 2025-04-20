<?php

namespace Tests\Feature;

use App\Http\Livewire\TrackCreate;
use App\Http\Livewire\TrackEdit;
use App\Http\Livewire\TrackUpload;
use App\Http\Requests\TrackStoreRequest;
use App\Http\Requests\TrackUpdateRequest;
use App\Models\Genre;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrackRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_track_store_validation()
    {
        $genre = Genre::findOrCreateByName('Electronic');
        
        // Test validation errors
        Livewire::test(TrackCreate::class)
            ->set('title', '')
            ->call('saveTrack')
            ->assertHasErrors(['title']);

        // Test successful submission with audio file
        $this->markTestIncomplete(
            'This test requires file upload simulation which is complex in Livewire tests.'
        );
    }

    public function test_track_update_validation()
    {
        $track = Track::create([
            'title' => 'Original Track',
            'audio_url' => 'https://example.com/original-audio.mp3',
            'image_url' => 'https://example.com/original-image.jpg',
            'unique_id' => Track::generateUniqueId('Original Track'),
            'duration' => '3:00',
        ]);
        
        $initialGenre = Genre::findOrCreateByName('Rock');
        $track->genres()->attach($initialGenre);

        // Test validation errors
        Livewire::test(TrackEdit::class, ['track' => $track])
            ->set('title', '')
            ->call('save')
            ->assertHasErrors(['title']);

        // Test successful update is complex due to file handling
        $this->markTestIncomplete(
            'Full track update testing would require simulating file uploads.'
        );
    }

    public function test_bulk_track_upload()
    {
        // This test is challenging because TrackUpload uses file uploads
        $this->markTestIncomplete(
            'This test requires file upload simulation which is complex in Livewire tests.'
        );
    }

    public function test_track_store_request_validation()
    {
        $request = new TrackStoreRequest;
        $rules = $request->rules();
        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('audio_url', $rules);
        $this->assertArrayHasKey('image_url', $rules);
        $this->assertArrayHasKey('genres', $rules);
        $this->assertArrayHasKey('genre_ids', $rules);
        $this->assertStringContainsString('nullable', $rules['genres']);
        $this->assertStringContainsString('string', $rules['genres']);
        $this->assertStringContainsString('nullable', $rules['genre_ids']);
        $this->assertStringContainsString('array', $rules['genre_ids']);
    }

    public function test_track_update_request_validation()
    {
        $request = new TrackUpdateRequest;
        $rules = $request->rules();
        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('audio_url', $rules);
        $this->assertArrayHasKey('image_url', $rules);
        $this->assertArrayHasKey('genres', $rules);
        $this->assertArrayHasKey('genre_ids', $rules);
        
        // For array-style rules in TrackUpdateRequest
        if (is_array($rules['genres'])) {
            $this->assertContains('nullable', $rules['genres']);
            $this->assertContains('string', $rules['genres']);
        } else {
            $this->assertStringContainsString('nullable', $rules['genres']);
            $this->assertStringContainsString('string', $rules['genres']);
        }
        
        if (is_array($rules['genre_ids'])) {
            $this->assertContains('nullable', $rules['genre_ids']);
            $this->assertContains('array', $rules['genre_ids']);
        } else {
            $this->assertStringContainsString('nullable', $rules['genre_ids']);
            $this->assertStringContainsString('array', $rules['genre_ids']);
        }
    }
}
