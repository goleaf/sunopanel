<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaylistControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    
    
    public function test_index_displays_playlists(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Playlist::factory()->count(3)->create();

        $response = $this->get(route('playlists.index'));

        $response->assertStatus(200);
        $response->assertViewIs('playlists.index');
        $response->assertViewHas('playlists');
    }

    /** @test */
    
    
    public function test_create_page_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('playlists.create'));

        $response->assertStatus(200);
        $response->assertViewIs('playlists.form');
        $response->assertViewHasAll(['playlist', 'genres', 'tracks']);
    }

    /** @test */
    
    
    public function test_store_playlist(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $genre = Genre::factory()->create();
        $tracks = Track::factory()->count(2)->create();
        $playlistData = [
            'title' => 'New Playlist',
            'description' => 'Playlist description',
            'genre_id' => $genre->id,
            'track_ids' => $tracks->pluck('id')->toArray(),
        ];

        $response = $this->post(route('playlists.store'), $playlistData);

        $response->assertRedirect(route('playlists.index'));
        $this->assertDatabaseHas('playlists', ['title' => 'New Playlist']);
        $playlist = Playlist::where('title', 'New Playlist')->first();
        $this->assertCount(2, $playlist->tracks);
    }

    /** @test */
    
    
    public function test_show_playlist(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $playlist = Playlist::factory()->has(Track::factory()->count(3))->create();

        $response = $this->get(route('playlists.show', $playlist));

        $response->assertStatus(200);
        $response->assertViewIs('playlists.show');
        $response->assertViewHas('playlist');
        $this->assertEquals($playlist->id, $response->viewData('playlist')->id);
        $this->assertCount(3, $response->viewData('playlist')->tracks);
    }

    /** @test */
    
    
    public function test_edit_page_loads(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $playlist = Playlist::factory()->create();

        $response = $this->get(route('playlists.edit', $playlist));

        $response->assertStatus(200);
        $response->assertViewIs('playlists.form');
        $response->assertViewHasAll(['playlist', 'genres', 'tracks']);
    }

    /** @test */
    
    
    public function test_update_playlist(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $playlist = Playlist::factory()->create();
        $genre = Genre::factory()->create();
        $tracks = Track::factory()->count(3)->create();
        $updatedData = [
            'title' => 'Updated Playlist Title',
            'description' => 'Updated description',
            'genre_id' => $genre->id,
            'track_ids' => $tracks->pluck('id')->toArray(),
        ];

        $response = $this->put(route('playlists.update', $playlist), $updatedData);

        $response->assertRedirect(route('playlists.index'));
        $this->assertDatabaseHas('playlists', ['id' => $playlist->id, 'title' => 'Updated Playlist Title']);
        $playlist->refresh();
        $this->assertEquals('Updated Playlist Title', $playlist->title);
        $this->assertEquals($genre->id, $playlist->genre_id);
        $this->assertCount(3, $playlist->tracks);
    }

    /** @test */
    
    
    public function test_destroy_playlist(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $playlist = Playlist::factory()->create();

        $response = $this->delete(route('playlists.destroy', $playlist));

        $response->assertRedirect(route('playlists.index'));
        $this->assertDatabaseMissing('playlists', ['id' => $playlist->id]);
    }

    /** @test */
    
    
    public function test_create_playlist_from_genre(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $genre = Genre::factory()->has(Track::factory()->count(5))->create();

        $response = $this->post(route('playlists.createFromGenre', $genre));

        $response->assertRedirect(route('playlists.index'));
        $this->assertDatabaseHas('playlists', ['title' => $genre->name . ' Playlist']);
        $playlist = Playlist::where('title', $genre->name . ' Playlist')->first();
        $this->assertNotNull($playlist);
        $this->assertCount(5, $playlist->tracks);
        $this->assertEquals($genre->id, $playlist->genre_id);
    }
}
