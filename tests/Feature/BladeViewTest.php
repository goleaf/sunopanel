<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BladeViewTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    public function test_tracks_index_view_renders()
    {
        $tracks = Track::factory(3)->create();
        $response = $this->get(route('tracks.index'));
        $response->assertStatus(200);
        $response->assertViewHas('tracks');
        foreach ($tracks as $track) {
            $trackId = $track->id;
            $response->assertViewHas('tracks', function ($viewTracks) use ($trackId) {
                return $viewTracks->contains('id', $trackId);
            });
        }
    }

    public function test_tracks_show_view_renders()
    {
        $track = Track::factory()->create(['title' => 'Test Track']);
        $genre = Genre::factory()->create(['name' => 'Rock']);
        $track->genres()->attach($genre);
        $response = $this->get(route('tracks.show', $track->id));
        $response->assertStatus(200);
        $response->assertViewHas('track');
        $response->assertSee('Test Track');
        $response->assertSee('Rock');
    }

    public function test_genres_index_view_renders()
    {
        $genres = Genre::factory(3)->create();
        $response = $this->get(route('genres.index'));
        $response->assertStatus(200);
        $response->assertViewHas('genres');
        foreach ($genres as $genre) {
            $response->assertSee($genre->name);
        }
    }

    public function test_playlists_index_view_renders()
    {
        $playlists = Playlist::factory(3)->create();
        $response = $this->get(route('playlists.index'));
        $response->assertStatus(200);
        $response->assertViewHas('playlists');
        foreach ($playlists as $playlist) {
            $response->assertSee($playlist->name);
        }
    }

    public function test_playlist_show_view_renders_correctly()
    {
        $playlist = Playlist::factory()->create([
            'title' => 'Test Playlist',
            'description' => 'This is a test playlist',
        ]);
        $tracks = Track::factory(3)->create();
        foreach ($tracks as $index => $track) {
            $playlist->tracks()->attach($track, ['position' => $index + 1]);
        }
        $response = $this->get(route('playlists.show', $playlist->id));
        $response->assertStatus(200);
        $response->assertViewHas('playlist');
        $response->assertSee('Test Playlist');
        $response->assertSee('This is a test playlist');

        foreach ($tracks as $track) {
            $response->assertSee($track->title);
        }
    }

    public function test_create_forms_render()
    {
        $response = $this->get(route('tracks.create'));
        $response->assertStatus(200);
        $response->assertSee('Add New Track');
        $response = $this->get(route('genres.create'));
        $response->assertStatus(200);
        $response->assertSee('Add New Genre');
        $response = $this->get(route('playlists.create'));
        $response->assertStatus(200);
        $response->assertSee('Create New Playlist');
    }

    public function test_edit_forms_render()
    {
        $track = Track::factory()->create();
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create();
        $response = $this->get(route('tracks.edit', $track->id));
        $response->assertStatus(200);
        $response->assertSee('Edit Track');
        $response = $this->get(route('genres.edit', $genre->id));
        $response->assertStatus(200);
        $response->assertSee('Edit Genre');
        $response = $this->get(route('playlists.edit', $playlist->id));
        $response->assertStatus(200);
        $response->assertSee('Edit Playlist');
    }
}
