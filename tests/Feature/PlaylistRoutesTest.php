<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaylistRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $genre = Genre::factory()->create(['name' => 'Bubblegum bass']);
        $playlist = Playlist::factory()->create([
            'title' => 'Test Playlist',
            'description' => 'A test playlist',
            'genre_id' => $genre->id,
        ]);

        $track = Track::factory()->create([
            'title' => 'Test Track',
            'audio_url' => 'https://example.com/setup_audio.mp3',
            'image_url' => 'https://example.com/setup_image.jpg',
        ]);

        $track->genres()->attach($genre->id);
        $playlist->tracks()->attach($track->id);
    }

    #[Test]
public function playlists_index_page_loads_correctly()
    {
        $response = $this->get(route('playlists.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Playlist');
        $response->assertViewIs('playlists.index');
    }

    #[Test]
public function playlist_show_page_loads_correctly()
    {
        $playlist = Playlist::first();

        $response = $this->get(route('playlists.show', $playlist));

        $response->assertStatus(200);
        $response->assertSee($playlist->title);
        $response->assertSee('Test Track');
        $response->assertViewIs('playlists.show');
    }

    #[Test]
public function playlist_create_page_loads_correctly()
    {
        $response = $this->get(route('playlists.create'));

        $response->assertStatus(200);
        $response->assertSee('Create Playlist');
        $response->assertViewIs('playlists.create');
    }

    #[Test]
public function playlist_edit_page_loads_correctly()
    {
        $playlist = Playlist::first();

        $response = $this->get(route('playlists.edit', $playlist));

        $response->assertStatus(200);
        $response->assertSee($playlist->title);
        $response->assertViewIs('playlists.edit');
    }

    #[Test]
public function playlist_can_be_created()
    {
        $genre = Genre::first();
        $track = Track::first();

        $response = $this->post(route('playlists.store'), [
            'title' => 'New Playlist',
            'description' => 'A new test playlist',
            'genre_id' => $genre->id,
            'track_ids' => [$track->id],
        ]);

        $response->assertRedirect(route('playlists.index'));
        $this->assertDatabaseHas('playlists', ['title' => 'New Playlist']);

        $playlist = Playlist::where('title', 'New Playlist')->first();
        $this->assertTrue($playlist->tracks->contains($track->id));
    }

    #[Test]
public function playlist_can_be_updated()
    {
        $playlist = Playlist::first();
        $genre = Genre::first();
        $track = Track::first();

        $response = $this->put(route('playlists.update', $playlist), [
            'title' => 'Updated Playlist',
            'description' => 'Updated description',
            'genre_id' => $genre->id,
            'track_ids' => [$track->id],
        ]);

        $response->assertRedirect(route('playlists.index'));
        $this->assertDatabaseHas('playlists', [
            'id' => $playlist->id,
            'title' => 'Updated Playlist',
            'description' => 'Updated description',
        ]);
    }

    #[Test]
public function playlist_can_be_deleted()
    {
        $playlist = Playlist::first();

        $response = $this->delete(route('playlists.destroy', $playlist));

        $response->assertRedirect(route('playlists.index'));
        $this->assertDatabaseMissing('playlists', ['id' => $playlist->id]);
    }

    #[Test]
public function playlists_can_be_searched()
    {
        Playlist::factory()->create(['title' => 'Another Playlist']);

        $response = $this->get(route('playlists.index', ['search' => 'Test']));

        $response->assertStatus(200);
        $response->assertSee('Test Playlist');
        $response->assertDontSee('Another Playlist');
    }

    #[Test]
public function playlist_can_be_created_from_genre()
    {
        $genre = Genre::first();

        $response = $this->post(route('playlists.create-from-genre', $genre));
        $newPlaylist = Playlist::where('title', "{$genre->name} Playlist")->first();
        $this->assertNotNull($newPlaylist);

        $response->assertRedirect(route('playlists.show', $newPlaylist));
        $this->assertDatabaseHas('playlists', [
            'title' => "{$genre->name} Playlist",
            'genre_id' => $genre->id,
        ]);
    }

    #[Test]
public function tracks_can_be_added_to_playlist()
    {
        $playlist = Playlist::first();
        $newTrack = Track::factory()->create([
            'title' => 'New Track',
            'audio_url' => 'https://example.com/new_audio.mp3',
            'image_url' => 'https://example.com/new_image.jpg',
        ]);

        $response = $this->post(route('playlists.store-tracks', $playlist), [
            'track_ids' => [$newTrack->id],
        ]);

        $response->assertRedirect(route('playlists.show', $playlist));
        $this->assertTrue($playlist->fresh()->tracks->contains($newTrack->id));
        $this->assertEquals(2, $playlist->fresh()->tracks->count());
    }
}
