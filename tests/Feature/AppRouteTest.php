<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_routes_are_accessible_without_auth(): void
    {
        $this->seed();
        $this->get('/')->assertStatus(200);
        $this->get('/tracks')->assertStatus(200);
        $this->get('/tracks/create')->assertStatus(200);
        $track = Track::first();
        $this->assertNotNull($track, 'No tracks found in database');
        $this->get("/tracks/{$track->id}")->assertStatus(200);
        $this->get("/tracks/{$track->id}/edit")->assertStatus(200);
        $this->get('/genres')->assertStatus(200);
        $this->get('/genres/create')->assertStatus(200);
        $genre = Genre::first();
        $this->assertNotNull($genre, 'No genres found in database');
        $this->get("/genres/{$genre->id}")->assertStatus(200);
        $this->get("/genres/{$genre->id}/edit")->assertStatus(200);
        $this->get('/playlists')->assertStatus(200);
        $this->get('/playlists/create')->assertStatus(200);
        $playlist = Playlist::first();
        if ($playlist) {
            $this->get("/playlists/{$playlist->id}")->assertStatus(200);
            $this->get("/playlists/{$playlist->id}/edit")->assertStatus(200);
            $this->get("/playlists/{$playlist->id}/add-tracks")->assertStatus(200);
        }
        $this->get('/system-stats')->assertStatus(200);
    }

    public function test_post_routes_work_with_auth(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/genres', [
            'name' => 'New Genre',
            'description' => 'This is a test genre',
        ]);

        $response->assertRedirect('/genres');
        $this->assertDatabaseHas('genres', ['name' => 'New Genre']);
        $genre = Genre::where('name', 'New Genre')->first();
        $this->assertNotNull($genre);

        $playlistData = [
            'title' => 'Test Playlist',
            'description' => 'This is a test playlist',
            'genre_id' => $genre->id,
        ];

        $response = $this->post('/playlists', $playlistData);

        // Get the newly created playlist to construct the correct redirect URL
        $playlist = Playlist::where('title', 'Test Playlist')->first();
        $this->assertNotNull($playlist, 'Playlist was not created successfully.');

        // Assert redirect to the route for adding tracks to the new playlist
        $response->assertRedirect(route('playlists.add_tracks', ['playlist' => $playlist]));
        $this->assertDatabaseHas('playlists', ['title' => 'Test Playlist']);
    }
}
