<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Track;
use App\Models\Genre;
use App\Models\Playlist;

class AppRouteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test basic routes in the application.
     */
    public function test_all_routes_are_accessible_without_auth(): void
    {
        // Seed the database
        $this->seed();
        
        // Basic pages
        $this->get('/')->assertStatus(200);
        
        // Track routes
        $this->get('/tracks')->assertStatus(200);
        $this->get('/tracks/create')->assertStatus(200);
        
        // Get a real track ID
        $track = Track::first();
        $this->assertNotNull($track, 'No tracks found in database');
        $this->get("/tracks/{$track->id}")->assertStatus(200);
        $this->get("/tracks/{$track->id}/edit")->assertStatus(200);
        
        // Genre routes
        $this->get('/genres')->assertStatus(200);
        $this->get('/genres/create')->assertStatus(200);
        
        // Get a real genre ID
        $genre = Genre::first();
        $this->assertNotNull($genre, 'No genres found in database');
        $this->get("/genres/{$genre->id}")->assertStatus(200);
        $this->get("/genres/{$genre->id}/edit")->assertStatus(200);
        
        // Playlist routes
        $this->get('/playlists')->assertStatus(200);
        $this->get('/playlists/create')->assertStatus(200);
        
        // Get a real playlist ID
        $playlist = Playlist::first();
        if ($playlist) {
            $this->get("/playlists/{$playlist->id}")->assertStatus(200);
            $this->get("/playlists/{$playlist->id}/edit")->assertStatus(200);
            $this->get("/playlists/{$playlist->id}/add-tracks")->assertStatus(200);
        }
        
        // System stats API route
        $this->get('/system-stats')->assertStatus(200);
    }
    
    /**
     * Test post routes with minimal data.
     */
    public function test_post_routes_work_without_auth(): void
    {
        // Creating a genre via POST request
        $response = $this->post('/genres', [
            'name' => 'New Genre',
            'description' => 'This is a test genre',
        ]);
        
        $response->assertRedirect('/genres');
        $this->assertDatabaseHas('genres', ['name' => 'New Genre']);
        
        // Get the created genre for the next test
        $genre = Genre::where('name', 'New Genre')->first();
        $this->assertNotNull($genre);
        
        $playlistData = [
            'title' => 'Test Playlist',
            'description' => 'This is a test playlist',
            'genre_id' => $genre->id,
        ];
        
        $response = $this->post('/playlists', $playlistData);
        $response->assertRedirect('/playlists');
        $this->assertDatabaseHas('playlists', ['title' => 'Test Playlist']);
    }
} 