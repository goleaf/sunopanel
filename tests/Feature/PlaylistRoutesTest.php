<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\Genre;

class PlaylistRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $genre = Genre::factory()->create(['name' => 'Bubblegum bass']);
        $playlist = Playlist::factory()->create([
            'title' => 'Test Playlist',
            'description' => 'A test playlist',
            'genre_id' => $genre->id
        ]);
        
        $track = Track::factory()->create([
            'title' => 'Test Track',
            'audio_url' => 'https://example.com/test.mp3',
            'image_url' => 'https://example.com/image.jpg',
        ]);
        
        $track->genres()->attach($genre->id);
        $playlist->tracks()->attach($track->id);
    }

    /** @test */
    public function playlists_index_page_loads_correctly()
    {
        $response = $this->get(route('playlists.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Test Playlist');
        $response->assertViewIs('playlists.index');
    }
    
    /** @test */
    public function playlist_show_page_loads_correctly()
    {
        $playlist = Playlist::first();
        
        $response = $this->get(route('playlists.show', $playlist));
        
        $response->assertStatus(200);
        $response->assertSee($playlist->title);
        $response->assertSee('Test Track');
        $response->assertViewIs('playlists.show');
    }
    
    /** @test */
    public function playlist_create_page_loads_correctly()
    {
        $response = $this->get(route('playlists.create'));
        
        $response->assertStatus(200);
        $response->assertSee('Create Playlist');
        $response->assertViewIs('playlists.create');
    }
    
    /** @test */
    public function playlist_edit_page_loads_correctly()
    {
        $playlist = Playlist::first();
        
        $response = $this->get(route('playlists.edit', $playlist));
        
        $response->assertStatus(200);
        $response->assertSee($playlist->title);
        $response->assertViewIs('playlists.edit');
    }
    
    /** @test */
    public function playlist_can_be_created()
    {
        $genre = Genre::first();
        $track = Track::first();
        
        $response = $this->post(route('playlists.store'), [
            'title' => 'New Playlist',
            'description' => 'A new test playlist',
            'genre_id' => $genre->id,
            'track_ids' => [$track->id]
        ]);
        
        $response->assertRedirect(route('playlists.index'));
        $this->assertDatabaseHas('playlists', ['title' => 'New Playlist']);
        
        $playlist = Playlist::where('title', 'New Playlist')->first();
        $this->assertTrue($playlist->tracks->contains($track->id));
    }
    
    /** @test */
    public function playlist_can_be_updated()
    {
        $playlist = Playlist::first();
        $genre = Genre::first();
        $track = Track::first();
        
        $response = $this->put(route('playlists.update', $playlist), [
            'title' => 'Updated Playlist',
            'description' => 'Updated description',
            'genre_id' => $genre->id,
            'track_ids' => [$track->id]
        ]);
        
        $response->assertRedirect(route('playlists.index'));
        $this->assertDatabaseHas('playlists', [
            'id' => $playlist->id, 
            'title' => 'Updated Playlist',
            'description' => 'Updated description'
        ]);
    }
    
    /** @test */
    public function playlist_can_be_deleted()
    {
        $playlist = Playlist::first();
        
        $response = $this->delete(route('playlists.destroy', $playlist));
        
        $response->assertRedirect(route('playlists.index'));
        $this->assertDatabaseMissing('playlists', ['id' => $playlist->id]);
    }
    
    /** @test */
    public function playlists_can_be_searched()
    {
        // Create additional playlist for search test
        Playlist::factory()->create(['title' => 'Another Playlist']);
        
        $response = $this->get(route('playlists.index', ['search' => 'Test']));
        
        $response->assertStatus(200);
        $response->assertSee('Test Playlist');
        $response->assertDontSee('Another Playlist');
    }
    
    /** @test */
    public function playlist_can_be_created_from_genre()
    {
        $genre = Genre::first();
        
        $response = $this->post(route('playlists.create-from-genre', $genre));
        
        // Get the newly created playlist
        $newPlaylist = Playlist::where('title', "{$genre->name} Playlist")->first();
        $this->assertNotNull($newPlaylist);
        
        $response->assertRedirect(route('playlists.show', $newPlaylist));
        
        // Verify a new playlist was created with the genre's name
        $this->assertDatabaseHas('playlists', [
            'title' => "{$genre->name} Playlist",
            'genre_id' => $genre->id
        ]);
    }
    
    /** @test */
    public function tracks_can_be_added_to_playlist()
    {
        $playlist = Playlist::first();
        
        // Create a new track to add to the playlist
        $newTrack = Track::factory()->create([
            'title' => 'New Track',
            'audio_url' => 'https://example.com/new.mp3',
            'image_url' => 'https://example.com/new.jpg',
        ]);
        
        $response = $this->post(route('playlists.store-tracks', $playlist), [
            'track_ids' => [$newTrack->id]
        ]);
        
        $response->assertRedirect(route('playlists.show', $playlist));
        
        // The playlist should now have both tracks
        $this->assertTrue($playlist->fresh()->tracks->contains($newTrack->id));
        $this->assertEquals(2, $playlist->fresh()->tracks->count());
    }
} 