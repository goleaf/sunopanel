<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaylistRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_playlist_store_validation()
    {
        // Test missing required fields
        $response = $this->post(route('playlists.store'), []);
        $response->assertSessionHasErrors(['title']);

        // Test valid data
        $validData = [
            'title' => 'Test Playlist',
            'description' => 'Test Description',
            'cover_image' => 'https://example.com/image.jpg',
        ];

        $response = $this->post(route('playlists.store'), $validData);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('playlists.index'));

        $this->assertDatabaseHas('playlists', [
            'title' => 'Test Playlist',
            'description' => 'Test Description',
        ]);
    }

    public function test_playlist_update_validation()
    {
        // Create a playlist
        $playlist = Playlist::create([
            'title' => 'Original Playlist',
            'description' => 'Original Description',
        ]);

        // Test valid data
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

    public function test_playlist_store_tracks_validation()
    {
        // Create a playlist and tracks
        $playlist = Playlist::create([
            'title' => 'Test Playlist',
            'description' => 'Test Description',
        ]);

        $track1 = Track::create([
            'title' => 'Test Track 1',
            'audio_url' => 'https://example.com/audio1.mp3',
            'image_url' => 'https://example.com/image1.jpg',
            'unique_id' => 'track1',
            'duration' => '3:00',
        ]);

        $track2 = Track::create([
            'title' => 'Test Track 2',
            'audio_url' => 'https://example.com/audio2.mp3',
            'image_url' => 'https://example.com/image2.jpg',
            'unique_id' => 'track2',
            'duration' => '3:30',
        ]);

        // Test missing required fields
        $response = $this->post(route('playlists.store-tracks', $playlist->id), []);
        $response->assertSessionHasErrors(['track_ids']);

        // Test valid data
        $validData = [
            'track_ids' => [$track1->id, $track2->id],
        ];

        $response = $this->post(route('playlists.store-tracks', $playlist->id), $validData);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('playlists.show', $playlist->id));

        // Check if tracks were attached to the playlist
        $this->assertDatabaseHas('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track1->id,
        ]);

        $this->assertDatabaseHas('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track2->id,
        ]);
    }

    public function test_playlist_remove_track_validation()
    {
        // Create a playlist and track
        $playlist = Playlist::create([
            'title' => 'Test Playlist',
            'description' => 'Test Description',
        ]);

        $track = Track::create([
            'title' => 'Test Track',
            'audio_url' => 'https://example.com/audio.mp3',
            'image_url' => 'https://example.com/image.jpg',
            'unique_id' => 'track1',
            'duration' => '3:00',
        ]);

        // Attach track to playlist
        $playlist->tracks()->attach($track->id, ['position' => 1]);

        // Test removing track from playlist
        $response = $this->delete(route('playlists.remove-track', [$playlist->id, $track->id]));
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('playlists.show', $playlist->id));

        // Check if track was detached from playlist
        $this->assertDatabaseMissing('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track->id,
        ]);
    }

    public function test_playlist_delete_validation()
    {
        // Create a playlist
        $playlist = Playlist::create([
            'title' => 'Test Playlist',
            'description' => 'Test Description',
        ]);

        // Test deleting playlist
        $response = $this->delete(route('playlists.destroy', $playlist->id));
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('playlists.index'));

        // Check if playlist was deleted
        $this->assertDatabaseMissing('playlists', [
            'id' => $playlist->id,
        ]);
    }

    public function test_playlist_create_from_genre_validation()
    {
        // Create a genre and tracks
        $genre = Genre::create([
            'name' => 'Test Genre',
            'description' => 'Test Description',
        ]);

        $track1 = Track::create([
            'title' => 'Test Track 1',
            'audio_url' => 'https://example.com/audio1.mp3',
            'image_url' => 'https://example.com/image1.jpg',
            'unique_id' => 'track1',
            'duration' => '3:00',
        ]);

        $track2 = Track::create([
            'title' => 'Test Track 2',
            'audio_url' => 'https://example.com/audio2.mp3',
            'image_url' => 'https://example.com/image2.jpg',
            'unique_id' => 'track2',
            'duration' => '3:30',
        ]);

        // Attach tracks to genre
        $genre->tracks()->attach([$track1->id, $track2->id]);

        // Test creating playlist from genre
        $response = $this->post(route('playlists.create-from-genre', $genre->id), [
            'title_suffix' => 'Custom Suffix',
        ]);
        $response->assertSessionHasNoErrors();

        // Check if playlist was created
        $this->assertDatabaseHas('playlists', [
            'genre_id' => $genre->id,
        ]);

        // Get the created playlist to check if tracks were attached
        $playlist = Playlist::where('genre_id', $genre->id)->first();

        // Check if tracks from the genre were attached to the playlist
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
