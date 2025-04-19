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

    /**
     * Test that tracks index view renders correctly.
     *
     * @return void
     */
    public function test_tracks_index_view_renders()
    {
        // Create some tracks
        $tracks = Track::factory(3)->create();
        
        // Visit the tracks index page
        $response = $this->get(route('tracks.index'));
        
        // Assert the response is successful
        $response->assertStatus(200);
        
        // Assert view has the expected variable
        $response->assertViewHas('tracks');
        
        // Assert the view contains the track titles
        foreach ($tracks as $track) {
            $response->assertSee($track->title);
        }
    }

    /**
     * Test that tracks show view renders correctly.
     *
     * @return void
     */
    public function test_tracks_show_view_renders()
    {
        // Create a track with a genre
        $track = Track::factory()->create(['title' => 'Test Track']);
        $genre = Genre::factory()->create(['name' => 'Rock']);
        $track->genres()->attach($genre);
        
        // Visit the track show page
        $response = $this->get(route('tracks.show', $track->id));
        
        // Assert the response is successful
        $response->assertStatus(200);
        
        // Assert view has the expected variable
        $response->assertViewHas('track');
        
        // Assert the view contains the track title and genre
        $response->assertSee('Test Track');
        $response->assertSee('Rock');
    }

    /**
     * Test that genres index view renders correctly.
     *
     * @return void
     */
    public function test_genres_index_view_renders()
    {
        // Create some genres
        $genres = Genre::factory(3)->create();
        
        // Visit the genres index page
        $response = $this->get(route('genres.index'));
        
        // Assert the response is successful
        $response->assertStatus(200);
        
        // Assert view has the expected variable
        $response->assertViewHas('genres');
        
        // Assert the view contains the genre names
        foreach ($genres as $genre) {
            $response->assertSee($genre->name);
        }
    }

    /**
     * Test that playlists index view renders correctly.
     *
     * @return void
     */
    public function test_playlists_index_view_renders()
    {
        // Create some playlists
        $playlists = Playlist::factory(3)->create();
        
        // Visit the playlists index page
        $response = $this->get(route('playlists.index'));
        
        // Assert the response is successful
        $response->assertStatus(200);
        
        // Assert view has the expected variable
        $response->assertViewHas('playlists');
        
        // Assert the view contains the playlist names
        foreach ($playlists as $playlist) {
            $response->assertSee($playlist->name);
        }
    }

    /**
     * Test that playlist show view renders correctly with tracks.
     *
     * @return void
     */
    public function test_playlist_show_view_renders_correctly()
    {
        // Create a playlist
        $playlist = Playlist::factory()->create([
            'title' => 'Test Playlist',
            'description' => 'This is a test playlist'
        ]);
        
        // Create some tracks and attach to playlist
        $tracks = Track::factory(3)->create();
        foreach ($tracks as $index => $track) {
            $playlist->tracks()->attach($track, ['position' => $index + 1]);
        }
        
        // Visit the playlist show page
        $response = $this->get(route('playlists.show', $playlist->id));
        
        // Assert the response is successful
        $response->assertStatus(200);
        
        // Assert view has the expected variable
        $response->assertViewHas('playlist');
        
        // Assert the view contains the playlist name, description and track titles
        $response->assertSee('Test Playlist');
        $response->assertSee('This is a test playlist');
        
        foreach ($tracks as $track) {
            $response->assertSee($track->title);
        }
    }

    /**
     * Test that the create forms render correctly.
     *
     * @return void
     */
    public function test_create_forms_render()
    {
        // Test track create form
        $response = $this->get(route('tracks.create'));
        $response->assertStatus(200);
        $response->assertSee('Add New Track');
        
        // Test genre create form
        $response = $this->get(route('genres.create'));
        $response->assertStatus(200);
        $response->assertSee('Add New Genre');
        
        // Test playlist create form
        $response = $this->get(route('playlists.create'));
        $response->assertStatus(200);
        $response->assertSee('Create New Playlist');
    }

    /**
     * Test that edit forms render correctly.
     *
     * @return void
     */
    public function test_edit_forms_render()
    {
        // Create models
        $track = Track::factory()->create();
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create();
        
        // Test track edit form
        $response = $this->get(route('tracks.edit', $track->id));
        $response->assertStatus(200);
        $response->assertSee('Edit Track');
        
        // Test genre edit form
        $response = $this->get(route('genres.edit', $genre->id));
        $response->assertStatus(200);
        $response->assertSee('Edit Genre');
        
        // Test playlist edit form
        $response = $this->get(route('playlists.edit', $playlist->id));
        $response->assertStatus(200);
        $response->assertSee('Edit Playlist');
    }
} 