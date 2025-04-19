<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaylistControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test playlist index page.
     */
    public function test_index_page_displays_playlists(): void
    {
        // Create some playlists
        $playlists = Playlist::factory()->count(3)->create();

        $response = $this->get('/playlists');

        $response->assertStatus(200);
        $response->assertViewIs('playlists.index');
        $response->assertViewHas('playlists');

        // Check if all playlists are displayed
        foreach ($playlists as $playlist) {
            $response->assertSee($playlist->title);
        }
    }

    /**
     * Test playlist create page.
     */
    public function test_create_page_loads(): void
    {
        // Create a genre to be available in the form
        Genre::factory()->create();

        $response = $this->get('/playlists/create');

        $response->assertStatus(200);
        $response->assertViewIs('playlists.create');
    }

    /**
     * Test storing a new playlist.
     */
    public function test_store_playlist(): void
    {
        $genre = Genre::factory()->create();

        $playlistData = [
            'title' => 'Test Playlist',
            'description' => 'This is a test playlist',
            'genre_id' => $genre->id,
        ];

        $response = $this->post('/playlists', $playlistData);

        $response->assertRedirect('/playlists');
        $this->assertDatabaseHas('playlists', [
            'title' => 'Test Playlist',
            'description' => 'This is a test playlist',
            'genre_id' => $genre->id,
        ]);
    }

    /**
     * Test showing a playlist.
     */
    public function test_show_playlist(): void
    {
        $playlist = Playlist::factory()->create();

        // Create and attach some tracks
        $tracks = Track::factory()->count(3)->create();
        $position = 0;

        foreach ($tracks as $track) {
            $playlist->tracks()->attach($track->id, ['position' => $position]);
            $position++;
        }

        $response = $this->get("/playlists/{$playlist->id}");

        $response->assertStatus(200);
        $response->assertViewIs('playlists.show');
        $response->assertViewHas('playlist');
        $response->assertSee($playlist->title);

        // Check if tracks are displayed
        foreach ($tracks as $track) {
            $response->assertSee($track->title);
        }
    }

    /**
     * Test editing a playlist.
     */
    public function test_edit_playlist(): void
    {
        $playlist = Playlist::factory()->create();

        $response = $this->get("/playlists/{$playlist->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('playlists.edit');
        $response->assertViewHas('playlist');
        $response->assertSee($playlist->title);
    }

    /**
     * Test updating a playlist.
     */
    public function test_update_playlist(): void
    {
        $playlist = Playlist::factory()->create();
        $newGenre = Genre::factory()->create();

        $updateData = [
            'title' => 'Updated Playlist',
            'description' => 'This is an updated description',
            'genre_id' => $newGenre->id,
        ];

        $response = $this->put("/playlists/{$playlist->id}", $updateData);

        $response->assertRedirect('/playlists');
        $this->assertDatabaseHas('playlists', [
            'id' => $playlist->id,
            'title' => 'Updated Playlist',
            'description' => 'This is an updated description',
            'genre_id' => $newGenre->id,
        ]);
    }

    /**
     * Test adding tracks to a playlist.
     */
    public function test_add_tracks_to_playlist(): void
    {
        $playlist = Playlist::factory()->create();
        $tracks = Track::factory()->count(3)->create();

        $response = $this->get("/playlists/{$playlist->id}/add-tracks");

        $response->assertStatus(200);
        $response->assertViewIs('playlists.add-tracks');
        $response->assertViewHas('playlist');
        $response->assertViewHas('tracks');

        // Add tracks to the playlist
        $trackIds = $tracks->pluck('id')->toArray();

        $response = $this->post("/playlists/{$playlist->id}/tracks", [
            'track_ids' => $trackIds,
        ]);

        $response->assertRedirect("/playlists/{$playlist->id}");

        // Check if tracks were added to the playlist
        foreach ($trackIds as $position => $trackId) {
            $this->assertDatabaseHas('playlist_track', [
                'playlist_id' => $playlist->id,
                'track_id' => $trackId,
                'position' => $position,
            ]);
        }
    }

    /**
     * Test removing a track from a playlist.
     */
    public function test_remove_track_from_playlist(): void
    {
        $playlist = Playlist::factory()->create();
        $track = Track::factory()->create();

        // Add track to playlist
        $playlist->tracks()->attach($track->id, ['position' => 0]);

        $response = $this->delete("/playlists/{$playlist->id}/tracks/{$track->id}");

        $response->assertRedirect("/playlists/{$playlist->id}");

        // Check if track was removed from the playlist
        $this->assertDatabaseMissing('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track->id,
        ]);
    }

    /**
     * Test deleting a playlist.
     */
    public function test_delete_playlist(): void
    {
        $playlist = Playlist::factory()->create();

        $response = $this->delete("/playlists/{$playlist->id}");

        $response->assertRedirect('/playlists');
        $this->assertDatabaseMissing('playlists', [
            'id' => $playlist->id,
        ]);
    }

    /**
     * Test creating a playlist from a genre.
     */
    public function test_create_playlist_from_genre(): void
    {
        // Create a genre with some tracks
        $genre = Genre::factory()->create();
        $tracks = Track::factory()->count(3)->create();

        // Associate tracks with the genre
        foreach ($tracks as $track) {
            $genre->tracks()->attach($track->id);
        }

        // Create the playlist from this genre
        $response = $this->post(route('playlists.create-from-genre', $genre->id));

        // Get the newly created playlist
        $newPlaylist = Playlist::latest()->first();

        // Assert the playlist was created
        $this->assertNotNull($newPlaylist);

        // Check redirect
        $response->assertRedirect(route('playlists.show', ['playlist' => $newPlaylist->id]));

        // Check if playlist was created with the correct genre
        $this->assertDatabaseHas('playlists', [
            'title' => "{$genre->name} Playlist",
            'genre_id' => $genre->id,
        ]);

        // Check if tracks were attached to the playlist
        foreach ($tracks as $track) {
            $this->assertDatabaseHas('playlist_track', [
                'playlist_id' => $newPlaylist->id,
                'track_id' => $track->id,
            ]);
        }
    }
}
