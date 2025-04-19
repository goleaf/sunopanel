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

    public function test_dashboard_loads_successfully(): void
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_genres_page_loads_successfully(): void
    {
        $response = $this->get('/genres');
        $response->assertStatus(200);
    }

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

    public function test_tracks_page_loads_successfully(): void
    {
        $response = $this->get('/tracks');
        $response->assertStatus(200);
    }

    public function test_can_create_track_with_genres(): void
    {
        $genre = Genre::factory()->create(['name' => 'Rock']);

        $trackData = [
            'title' => 'Test Track',
            'audio_url' => 'https:
            'image_url' => 'https:
            'genres' => 'Rock, Pop',
        ];

        $response = $this->post('/tracks', $trackData);

        $response->assertRedirect('/tracks');
        $this->assertDatabaseHas('tracks', ['title' => 'Test Track']);
        $track = Track::where('title', 'Test Track')->first();
        $this->assertTrue($track->genres->contains('name', 'Rock'));
        $this->assertTrue($track->genres->contains('name', 'Pop'));
    }

    public function test_playlists_page_loads_successfully(): void
    {
        $response = $this->get('/playlists');
        $response->assertStatus(200);
    }

    public function test_can_create_playlist(): void
    {
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

    public function test_can_add_tracks_to_playlist(): void
    {
        $genre = Genre::factory()->create();
        $tracks = Track::factory()->count(3)->create();
        $playlist = Playlist::factory()->create(['genre_id' => $genre->id]);
        $trackIds = $tracks->pluck('id')->toArray();

        $response = $this->post("/playlists/{$playlist->id}/tracks", [
            'track_ids' => $trackIds,
        ]);

        $response->assertRedirect("/playlists/{$playlist->id}");
        foreach ($trackIds as $trackId) {
            $this->assertDatabaseHas('playlist_track', [
                'playlist_id' => $playlist->id,
                'track_id' => $trackId,
            ]);
        }
    }

    public function test_can_search_tracks(): void
    {
        $rockTrack = Track::factory()->create(['title' => 'Rock Song']);
        $popTrack = Track::factory()->create(['title' => 'Pop Song']);
        $classicalTrack = Track::factory()->create(['title' => 'Classical Song']);
        $response = $this->get('/tracks?search=Rock');

        $response->assertStatus(200);
        $response->assertViewHas('tracks', function ($tracks) {
            return $tracks->contains('title', 'Rock Song');
        });
    }

    public function test_can_filter_tracks_by_genre(): void
    {
        $rock = Genre::factory()->create(['name' => 'Rock']);
        $pop = Genre::factory()->create(['name' => 'Pop']);
        $rockTrack = Track::factory()->create(['title' => 'Rock Song']);
        $popTrack = Track::factory()->create(['title' => 'Pop Song']);
        $rockTrack->genres()->attach($rock->id);
        $popTrack->genres()->attach($pop->id);
        $response = $this->get("/tracks?genre={$rock->id}");

        $response->assertStatus(200);
        $response->assertViewHas('tracks', function ($tracks) {
            return $tracks->contains('title', 'Rock Song') && ! $tracks->contains('title', 'Pop Song');
        });
    }
}
