<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Genre;
use App\Models\Track;

class GenreRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $genre = Genre::factory()->create(['name' => 'Bubblegum bass']);
        $track = Track::factory()->create([
            'title' => 'Test Track',
            'audio_url' => 'https://example.com/test.mp3',
            'image_url' => 'https://example.com/image.jpg',
        ]);
        
        $track->genres()->attach($genre->id);
    }

    /** @test */
    public function genres_index_page_loads_correctly()
    {
        $response = $this->get(route('genres.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Bubblegum bass');
        $response->assertViewIs('genres.index');
    }
    
    /** @test */
    public function genre_show_page_loads_correctly()
    {
        $genre = Genre::first();
        
        $response = $this->get(route('genres.show', $genre));
        
        $response->assertStatus(200);
        $response->assertSee($genre->name);
        $response->assertSee('Test Track');
        $response->assertViewIs('genres.show');
    }
    
    /** @test */
    public function genre_create_page_loads_correctly()
    {
        $response = $this->get(route('genres.create'));
        
        $response->assertStatus(200);
        $response->assertSee('Create Genre');
        $response->assertViewIs('genres.create');
    }
    
    /** @test */
    public function genre_edit_page_loads_correctly()
    {
        $genre = Genre::first();
        
        $response = $this->get(route('genres.edit', $genre));
        
        $response->assertStatus(200);
        $response->assertSee($genre->name);
        $response->assertViewIs('genres.edit');
    }
    
    /** @test */
    public function genre_can_be_created()
    {
        $response = $this->post(route('genres.store'), [
            'name' => 'Rock',
            'description' => 'Rock music genre'
        ]);
        
        $response->assertRedirect(route('genres.index'));
        $this->assertDatabaseHas('genres', ['name' => 'Rock']);
    }
    
    /** @test */
    public function genre_can_be_updated()
    {
        $genre = Genre::first();
        
        $response = $this->put(route('genres.update', $genre), [
            'name' => 'Updated Genre',
            'description' => 'Updated description'
        ]);
        
        $response->assertRedirect(route('genres.index'));
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id, 
            'name' => 'Updated Genre',
            'description' => 'Updated description'
        ]);
    }
    
    /** @test */
    public function genre_can_be_deleted()
    {
        $genre = Genre::first();
        
        $response = $this->delete(route('genres.destroy', $genre));
        
        $response->assertRedirect(route('genres.index'));
        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }
    
    /** @test */
    public function genres_can_be_searched()
    {
        // Create additional genre for search test
        Genre::factory()->create(['name' => 'Rock']);
        
        $response = $this->get(route('genres.index', ['search' => 'bubblegum']));
        
        $response->assertStatus(200);
        $response->assertSee('Bubblegum bass');
        $response->assertDontSee('Rock');
    }
    
    /** @test */
    public function genre_shows_associated_tracks()
    {
        $genre = Genre::first();
        
        // Create more tracks for this genre
        $track2 = Track::factory()->create([
            'title' => 'Another Test Track',
            'audio_url' => 'https://example.com/another.mp3',
            'image_url' => 'https://example.com/another.jpg',
        ]);
        $track2->genres()->attach($genre->id);
        
        $response = $this->get(route('genres.show', $genre));
        
        $response->assertStatus(200);
        $response->assertSee('Test Track');
        $response->assertSee('Another Test Track');
    }
} 