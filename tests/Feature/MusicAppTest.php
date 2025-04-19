<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MusicAppTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test if the dashboard page loads correctly.
     */
    public function test_dashboard_loads_successfully(): void
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    /**
     * Test if the genres page loads correctly.
     */
    public function test_genres_page_loads_successfully(): void
    {
        $response = $this->get('/genres');
        $response->assertStatus(200);
    }

    /**
     * Test if we can create a genre.
     */
    public function test_can_create_genre(): void
    {
        $genreData = [
            'name' => 'Test Genre',
            'description' => 'This is a test genre',
        ];

        $response = $this->post('/genres', $genreData);

        $response->assertRedirect('/genres');
        $this->assertDatabaseHas('genres', ['name' => 'Test Genre']);
    }

    /**
     * Test if the tracks page loads correctly.
     */
    public function test_tracks_page_loads_successfully(): void
    {
        $response = $this->get('/tracks');
        $response->assertStatus(200);
    }

    /**
     * Test if we can create a track with genres.
     */
    public function test_can_create_track_with_genres(): void
    {
        // Create a genre first
        $genre = Genre::factory()->create(['name' => 'Rock']);

        $trackData = [
            'title' => 'Test Track',
            'audio_url' => 'https://example.com/audio.mp3',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => 'Rock, Pop',
        ];

        $response = $this->post('/tracks', $trackData);

        $response->assertRedirect('/tracks');
        $this->assertDatabaseHas('tracks', ['title' => 'Test Track']);

        // Get the created track
        $track = Track::where('title', 'Test Track')->first();

        // Check if the genres were associated correctly
        $this->assertTrue($track->genres->contains('name', 'Rock'));
        $this->assertTrue($track->genres->contains('name', 'Pop'));
    }

    /**
     * Test if the playlists page loads correctly.
     */
    public function test_playlists_page_loads_successfully(): void
    {
        $response = $this->get('/playlists');
        $response->assertStatus(200);
    }

    /**
     * Test creating a playlist.
     */
    public function test_can_create_playlist(): void
    {
        // Create a genre for the playlist
        $genre = Genre::factory()->create();

        $playlistData = [
            'title' => 'Test Playlist',
            'description' => 'This is a test playlist',
            'genre_id' => $genre->id,
        ];

        $response = $this->post('/playlists', $playlistData);

        $response->assertRedirect('/playlists');
        $this->assertDatabaseHas('playlists', ['title' => 'Test Playlist']);
    }

    /**
     * Test if we can add tracks to a playlist.
     */
    public function test_can_add_tracks_to_playlist(): void
    {
        // Create a genre, tracks, and a playlist
        $genre = Genre::factory()->create();
        $tracks = Track::factory()->count(3)->create();
        $playlist = Playlist::factory()->create(['genre_id' => $genre->id]);

        // Add tracks to the playlist
        $trackIds = $tracks->pluck('id')->toArray();

        $response = $this->post("/playlists/{$playlist->id}/tracks", [
            'track_ids' => $trackIds,
        ]);

        $response->assertRedirect("/playlists/{$playlist->id}");

        // Check if the tracks were added to the playlist
        foreach ($trackIds as $trackId) {
            $this->assertDatabaseHas('playlist_track', [
                'playlist_id' => $playlist->id,
                'track_id' => $trackId,
            ]);
        }
    }

    /**
     * Test search functionality.
     */
    public function test_can_search_tracks(): void
    {
        // Create some tracks
        $rockTrack = Track::factory()->create(['title' => 'Rock Song']);
        $popTrack = Track::factory()->create(['title' => 'Pop Song']);
        $classicalTrack = Track::factory()->create(['title' => 'Classical Song']);

        // Search for "Rock"
        $response = $this->get('/tracks?search=Rock');

        $response->assertStatus(200);
        // Use assertViewHas to check that the track is in the collection instead of assertSee
        $response->assertViewHas('tracks', function ($tracks) {
            return $tracks->contains('title', 'Rock Song');
        });
    }

    /**
     * Test filtering by genre.
     */
    public function test_can_filter_tracks_by_genre(): void
    {
        // Create genres
        $rock = Genre::factory()->create(['name' => 'Rock']);
        $pop = Genre::factory()->create(['name' => 'Pop']);

        // Create tracks
        $rockTrack = Track::factory()->create(['title' => 'Rock Song']);
        $popTrack = Track::factory()->create(['title' => 'Pop Song']);

        // Associate genres with tracks
        $rockTrack->genres()->attach($rock->id);
        $popTrack->genres()->attach($pop->id);

        // Filter by Rock genre
        $response = $this->get("/tracks?genre={$rock->id}");

        $response->assertStatus(200);
        // Use assertViewHas to check that the track is in the collection instead of assertSee
        $response->assertViewHas('tracks', function ($tracks) {
            return $tracks->contains('title', 'Rock Song') && ! $tracks->contains('title', 'Pop Song');
        });
    }
}
