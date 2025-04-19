<?php

namespace Tests\Feature;

use App\Http\Requests\TrackStoreRequest;
use App\Http\Requests\TrackUpdateRequest;
use App\Models\Genre;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_track_store_validation()
    {
        // Create a genre for testing
        $genre = Genre::findOrCreateByName('Electronic');

        // Test missing required fields
        $response = $this->post(route('tracks.store'), []);
        $response->assertSessionHasErrors(['title', 'audio_url', 'image_url']);
        // Either genres or genre_ids must be present
        $response->assertSessionHasErrors(['genres']);

        // Test valid data with genres string
        $validData = [
            'title' => 'Test Track',
            'audio_url' => 'https://example.com/audio.mp3',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => 'Bubblegum bass',
            'duration' => '3:30',
        ];

        $response = $this->post(route('tracks.store'), $validData);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('tracks.index'));

        $this->assertDatabaseHas('tracks', [
            'title' => 'Test Track',
            'audio_url' => 'https://example.com/audio.mp3',
            'image_url' => 'https://example.com/image.jpg',
            'duration' => '3:30',
        ]);

        // Verify the "Bubblegum bass" genre was properly assigned
        $track = Track::where('title', 'Test Track')->first();
        $this->assertTrue($track->genres()->where('name', 'Bubblegum bass')->exists());

        // Test valid data with genre_ids array
        $validDataWithGenreIds = [
            'title' => 'Test Track with Genre IDs',
            'audio_url' => 'https://example.com/audio2.mp3',
            'image_url' => 'https://example.com/image2.jpg',
            'genre_ids' => [$genre->id],
            'duration' => '4:30',
        ];

        $response = $this->withoutExceptionHandling()->post(route('tracks.store'), $validDataWithGenreIds);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('tracks.index'));

        $this->assertDatabaseHas('tracks', [
            'title' => 'Test Track with Genre IDs',
            'audio_url' => 'https://example.com/audio2.mp3',
            'image_url' => 'https://example.com/image2.jpg',
            'duration' => '4:30',
        ]);

        // Verify the genre was properly assigned
        $track2 = Track::where('title', 'Test Track with Genre IDs')->first();
        $this->assertTrue($track2->genres()->where('genres.id', $genre->id)->exists());
    }

    public function test_track_update_validation()
    {
        // Create a track
        $track = Track::create([
            'title' => 'Original Track',
            'audio_url' => 'https://example.com/original-audio.mp3',
            'image_url' => 'https://example.com/original-image.jpg',
            'unique_id' => Track::generateUniqueId('Original Track'),
            'duration' => '3:00',
        ]);

        // Assign an initial genre
        $genre = Genre::findOrCreateByName('Rock');
        $track->genres()->attach($genre);

        // Test missing required fields
        $response = $this->put(route('tracks.update', $track->id), []);
        $response->assertSessionHasErrors(['title', 'audio_url', 'image_url']);
        // Either genres or genre_ids must be present
        $response->assertSessionHasErrors(['genres']);

        // Test valid data with genres string
        $validData = [
            'title' => 'Updated Track',
            'audio_url' => 'https://example.com/updated-audio.mp3',
            'image_url' => 'https://example.com/updated-image.jpg',
            'genres' => 'bubblegum bass, Chillwave',  // lowercase intentionally to test case-insensitivity
            'duration' => '4:15',
        ];

        $response = $this->put(route('tracks.update', $track->id), $validData);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('tracks.index'));

        $this->assertDatabaseHas('tracks', [
            'id' => $track->id,
            'title' => 'Updated Track',
            'audio_url' => 'https://example.com/updated-audio.mp3',
            'image_url' => 'https://example.com/updated-image.jpg',
            'duration' => '4:15',
        ]);

        // Verify genres were properly updated
        $track->refresh();
        $genreNames = $track->genres->pluck('name')->toArray();
        $this->assertContains('Bubblegum bass', $genreNames);
        $this->assertContains('Chillwave', $genreNames);
        $this->assertCount(2, $genreNames);

        // Test update with genre_ids
        $genre1 = Genre::findOrCreateByName('Techno');
        $genre2 = Genre::findOrCreateByName('EDM');

        $validDataWithGenreIds = [
            'title' => 'Another Update',
            'audio_url' => 'https://example.com/another-audio.mp3',
            'image_url' => 'https://example.com/another-image.jpg',
            'genre_ids' => [$genre1->id, $genre2->id],
            'duration' => '5:15',
        ];

        $response = $this->withoutExceptionHandling()->put(route('tracks.update', $track->id), $validDataWithGenreIds);
        $response->assertSessionHasNoErrors();

        // Verify genres were properly updated
        $track->refresh();
        $genreIds = $track->genres->pluck('id')->toArray();
        $this->assertContains($genre1->id, $genreIds);
        $this->assertContains($genre2->id, $genreIds);
        $this->assertCount(2, $genreIds);
    }

    public function test_bulk_track_upload()
    {
        // Test bulk track upload directly through the bulk upload route
        $bulkData = "Test Track 1|https://example.com/audio1.mp3|https://example.com/image1.jpg|Bubblegum bass\n";
        $bulkData .= 'Test Track 2|https://example.com/audio2.mp3|https://example.com/image2.jpg|chillwave';

        // Use withoutExceptionHandling to get more detailed error messages
        $response = $this->withoutExceptionHandling()
            ->post(route('tracks.bulk-upload'), [
                'bulk_tracks' => $bulkData,
            ]);

        // Check that the request was successful and redirected to tracks.index
        $response->assertRedirect(route('tracks.index'));

        // Verify tracks were created in the database
        $this->assertDatabaseHas('tracks', [
            'title' => 'Test Track 1',
            'audio_url' => 'https://example.com/audio1.mp3',
            'image_url' => 'https://example.com/image1.jpg',
        ]);

        $this->assertDatabaseHas('tracks', [
            'title' => 'Test Track 2',
            'audio_url' => 'https://example.com/audio2.mp3',
            'image_url' => 'https://example.com/image2.jpg',
        ]);

        // Check if genres were created and attached properly with correct capitalization
        $track1 = Track::where('title', 'Test Track 1')->first();
        $this->assertTrue($track1->genres()->where('name', 'Bubblegum bass')->exists());

        $track2 = Track::where('title', 'Test Track 2')->first();
        $this->assertTrue($track2->genres()->where('name', 'Chillwave')->exists());
    }

    public function test_track_store_request_validation()
    {
        // Test TrackStoreRequest validation rules directly
        $request = new TrackStoreRequest;
        $rules = $request->rules();

        // Verify critical validation rules exist
        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('audio_url', $rules);
        $this->assertArrayHasKey('image_url', $rules);
        $this->assertArrayHasKey('genres', $rules);
        $this->assertArrayHasKey('genre_ids', $rules);

        // Check required_without relationship between genres and genre_ids
        $this->assertContains('required_without:genre_ids', $rules['genres']);
        $this->assertContains('required_without:genres', $rules['genre_ids']);
    }

    public function test_track_update_request_validation()
    {
        // Test TrackUpdateRequest validation rules directly
        $request = new TrackUpdateRequest;
        $rules = $request->rules();

        // Verify critical validation rules exist
        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('audio_url', $rules);
        $this->assertArrayHasKey('image_url', $rules);
        $this->assertArrayHasKey('genres', $rules);
        $this->assertArrayHasKey('genre_ids', $rules);

        // Check required_without relationship between genres and genre_ids
        $this->assertContains('required_without:genre_ids', $rules['genres']);
        $this->assertContains('required_without:genres', $rules['genre_ids']);
    }
}
