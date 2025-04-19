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
        $genre = Genre::findOrCreateByName('Electronic');
        $response = $this->post(route('tracks.store'), []);
        $response->assertSessionHasErrors(['title', 'audio_url']);

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
        $track = Track::where('title', 'Test Track with Genre IDs')->first();
        $this->assertNotNull($track, 'Track should be created');
        $this->assertTrue($track->genres()->where('genres.id', $genre->id)->exists(), 'Genre should be associated');
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

        $response = $this->put(route('tracks.update', $track->id), []);
        $response->assertSessionHasErrors(['title', 'audio_url']);

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
        $response->assertRedirect(route('tracks.show', $track));

        $this->assertDatabaseHas('tracks', [
            'id' => $track->id,
            'title' => 'Another Update',
            'audio_url' => 'https://example.com/another-audio.mp3',
            'image_url' => 'https://example.com/another-image.jpg',
            'duration' => '5:15',
        ]);

        $track->refresh();
        $genreIds = $track->genres->pluck('id')->toArray();
        $this->assertContains($genre1->id, $genreIds, 'Genre 1 should be associated');
        $this->assertContains($genre2->id, $genreIds, 'Genre 2 should be associated');
        $this->assertNotContains($initialGenre->id, $genreIds, 'Initial genre should be detached');
        $this->assertCount(2, $genreIds, 'Should have exactly 2 genres associated');
    }

    public function test_bulk_track_upload()
    {
        $bulkData = "Test Track 1|https://example.com/bulk-audio1.mp3|https://example.com/bulk-image1.jpg|Bubblegum bass|3:45";
        $bulkData .= "\nTest Track 2|https://example.com/bulk-audio2.mp3|https://example.com/bulk-image2.jpg|Chillwave|4:20";

        $response = $this->withoutExceptionHandling()
            ->post(route('tracks.bulk-upload'), [
                'bulk_tracks' => $bulkData,
            ]);
        $response->assertRedirect(route('tracks.index'));
        $this->assertDatabaseHas('tracks', [
            'title' => 'Test Track 1',
            'audio_url' => 'https://example.com/bulk-audio1.mp3',
            'image_url' => 'https://example.com/bulk-image1.jpg',
        ]);

        $this->assertDatabaseHas('tracks', [
            'title' => 'Test Track 2',
            'audio_url' => 'https://example.com/bulk-audio2.mp3',
            'image_url' => 'https://example.com/bulk-image2.jpg',
        ]);
        $track1 = Track::where('title', 'Test Track 1')->first();
        $this->assertTrue($track1->genres()->where('name', 'Bubblegum bass')->exists());

        $track2 = Track::where('title', 'Test Track 2')->first();
        $this->assertTrue($track2->genres()->where('name', 'Chillwave')->exists());
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
        $this->assertContains('nullable', $rules['genres']);
        $this->assertContains('string', $rules['genres']);
        $this->assertContains('nullable', $rules['genre_ids']);
        $this->assertContains('array', $rules['genre_ids']);
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
        $this->assertContains('nullable', $rules['genres']);
        $this->assertContains('string', $rules['genres']);
        $this->assertContains('nullable', $rules['genre_ids']);
        $this->assertContains('array', $rules['genre_ids']);
    }
}
