<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaylistRequestTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_playlist_store_validation(): void {
        $response = $this->post(route('playlists.store'), []);
        $response->assertSessionHasErrors(['title']);
        $validData = [
            'title' => 'Test Playlist',
            'description' => 'Test Description',
            'cover_image' => 'https://example.com/cover.jpg'
        ];

        $response = $this->post(route('playlists.store'), $validData);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('playlists.index'));

        $this->assertDatabaseHas('playlists', [
            'title' => 'Test Playlist',
            'description' => 'Test Description',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_playlist_update_validation(): void {
        $playlist = Playlist::create([
            'title' => 'Original Playlist',
            'description' => 'Original Description',
        ]);
        $validData = [
            'title' => 'Updated Playlist',
            'description' => 'Updated Description',
        ];

        $response = $this->put(route('playlists.update', $playlist->id), $validData);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('playlists.index'));

        $this->assertDatabaseHas('playlists', [
            'id' => $playlist->id,
            'title' => 'Updated Playlist',
            'description' => 'Updated Description',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_playlist_store_tracks_validation(): void {
        $playlist = Playlist::create([
            'title' => 'Test Playlist',
            'description' => 'Test Description',
        ]);

        $track1 = Track::create([
            'title' => 'Test Track 1',
            'audio_url' => 'https://example.com/track1_audio.mp3',
            'image_url' => 'https://example.com/track1_image.jpg',
            'unique_id' => 'track1',
            'duration' => '3:00',
        ]);

        $track2 = Track::create([
            'title' => 'Test Track 2',
            'audio_url' => 'https://example.com/track2_audio.mp3',
            'image_url' => 'https://example.com/track2_image.jpg',
            'unique_id' => 'track2',
            'duration' => '3:30',
        ]);
        $response = $this->post(route('playlists.store-tracks', $playlist->id), []);
        $response->assertSessionHasErrors(['track_ids']);
        $validData = [
            'track_ids' => [$track1->id, $track2->id],
        ];

        $response = $this->post(route('playlists.store-tracks', $playlist->id), $validData);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('playlists.show', $playlist->id));
        $this->assertDatabaseHas('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track1->id,
        ]);

        $this->assertDatabaseHas('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track2->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_playlist_remove_track_validation(): void {
        $playlist = Playlist::create([
            'title' => 'Test Playlist',
            'description' => 'Test Description',
        ]);

        $track = Track::create([
            'title' => 'Test Track',
            'audio_url' => 'https://example.com/rem_audio.mp3',
            'image_url' => 'https://example.com/rem_image.jpg',
            'unique_id' => 'track1',
            'duration' => '3:00',
        ]);
        $playlist->tracks()->attach($track->id, ['position' => 1]);
        $response = $this->delete(route('playlists.remove-track', [$playlist->id, $track->id]));
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('playlists.show', $playlist->id));
        $this->assertDatabaseMissing('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_playlist_delete_validation(): void {
        $playlist = Playlist::create([
            'title' => 'Test Playlist',
            'description' => 'Test Description',
        ]);
        $response = $this->delete(route('playlists.destroy', $playlist->id));
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('playlists.index'));
        $this->assertDatabaseMissing('playlists', [
            'id' => $playlist->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_playlist_create_from_genre_validation(): void {
        $genre = Genre::create([
            'name' => 'Test Genre',
            'description' => 'Test Description',
        ]);

        $track1 = Track::create([
            'title' => 'Test Track 1',
            'audio_url' => 'https://example.com/genre_audio1.mp3',
            'image_url' => 'https://example.com/genre_image1.jpg',
            'unique_id' => 'track1',
            'duration' => '3:00',
        ]);

        $track2 = Track::create([
            'title' => 'Test Track 2',
            'audio_url' => 'https://example.com/genre_audio2.mp3',
            'image_url' => 'https://example.com/genre_image2.jpg',
            'unique_id' => 'track2',
            'duration' => '3:30',
        ]);
        $genre->tracks()->attach([$track1->id, $track2->id]);
        $response = $this->post(route('playlists.create-from-genre', $genre->id), [
            'title_suffix' => 'Custom Suffix',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('playlists', [
            'genre_id' => $genre->id,
        ]);
        $playlist = Playlist::where('genre_id', $genre->id)->first();
        $this->assertDatabaseHas('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track1->id,
        ]);

        $this->assertDatabaseHas('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track2->id,
        ]);
    }
}
