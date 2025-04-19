<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Genre;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;

class GenreControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user and authenticate
        $this->actingAs(User::factory()->create());
    }

    /**
     * Test genre index page.
     */
    public function test_index_page_displays_genres(): void
    {
        // Create some genres
        $genres = Genre::factory()->count(3)->create();
        
        $response = $this->get('/genres');
        
        $response->assertStatus(200);
        $response->assertViewIs('genres.index');
        $response->assertViewHas('genres');
        
        // Check if all genres are displayed
        foreach ($genres as $genre) {
            $response->assertSee($genre->name);
        }
    }
    
    /**
     * Test genre create page.
     */
    public function test_create_page_loads(): void
    {
        $response = $this->get('/genres/create');
        
        $response->assertStatus(200);
        $response->assertViewIs('genres.create');
    }
    
    /**
     * Test storing a new genre.
     */
    public function test_store_genre(): void
    {
        $genreData = [
            'name' => 'Test Genre',
            'description' => 'This is a test genre',
        ];
        
        $response = $this->post('/genres', $genreData);
        
        $response->assertRedirect('/genres');
        $this->assertDatabaseHas('genres', [
            'name' => 'Test Genre',
            'description' => 'This is a test genre',
        ]);
    }
    
    /**
     * Test showing a genre.
     */
    public function test_show_genre(): void
    {
        $genre = Genre::factory()->create();
        
        // Associate some tracks with the genre
        $tracks = Track::factory()->count(3)->create();
        foreach ($tracks as $track) {
            $track->genres()->attach($genre->id);
        }
        
        $response = $this->get("/genres/{$genre->id}");
        
        $response->assertStatus(200);
        $response->assertViewIs('genres.show');
        $response->assertViewHas('genre');
        $response->assertSee($genre->name);
        
        // Check if tracks are displayed
        foreach ($tracks as $track) {
            $response->assertSee($track->name);
        }
    }
    
    /**
     * Test editing a genre.
     */
    public function test_edit_genre(): void
    {
        $genre = Genre::factory()->create();
        
        $response = $this->get("/genres/{$genre->id}/edit");
        
        $response->assertStatus(200);
        $response->assertViewIs('genres.edit');
        $response->assertViewHas('genre');
        $response->assertSee($genre->name);
    }
    
    /**
     * Test updating a genre.
     */
    public function test_update_genre(): void
    {
        $genre = Genre::factory()->create();
        
        $updateData = [
            'name' => 'Updated Genre',
            'description' => 'This is an updated description',
        ];
        
        $response = $this->put("/genres/{$genre->id}", $updateData);
        
        $response->assertRedirect('/genres');
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'Updated Genre',
            'description' => 'This is an updated description',
        ]);
    }
    
    /**
     * Test deleting a genre.
     */
    public function test_delete_genre(): void
    {
        $genre = Genre::factory()->create();
        
        $response = $this->delete("/genres/{$genre->id}");
        
        $response->assertRedirect('/genres');
        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }
    
    /**
     * Test finding or creating a genre by name.
     */
    public function test_find_or_create_by_name(): void
    {
        // Ensure clean state for this test
        Genre::truncate();
        
        // Create a genre first
        $existingGenre = Genre::factory()->create(['name' => 'Existing Genre']);
        
        // Find the existing genre
        $foundGenre = Genre::findOrCreateByName('Existing Genre');
        $this->assertEquals($existingGenre->id, $foundGenre->id);
        
        // Create a new genre
        $newGenre = Genre::findOrCreateByName('New Genre');
        $this->assertDatabaseHas('genres', [
            'name' => 'New genre',
        ]);
    }

    /**
     * Test the genres index page.
     */
    public function test_genres_index_page()
    {
        // Create some genres for testing
        Genre::factory()->count(5)->create();
        
        $response = $this->get(route('genres.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('genres.index');
        $response->assertViewHas('genres');
    }

    /**
     * Test the genre creation form.
     */
    public function test_genre_create_form()
    {
        $response = $this->get(route('genres.create'));
        
        $response->assertStatus(200);
        $response->assertViewIs('genres.create');
    }

    /**
     * Test storing a new genre.
     */
    public function test_genre_store()
    {
        $genreData = [
            'name' => 'Test Genre',
            'description' => 'This is a test genre description',
        ];
        
        $response = $this->post(route('genres.store'), $genreData);
        
        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success');
        
        // Check database for the genre
        $this->assertDatabaseHas('genres', [
            'name' => 'Test Genre',
            'description' => 'This is a test genre description',
        ]);
    }

    /**
     * Test validation for storing a genre.
     */
    public function test_genre_store_validation()
    {
        // Create a genre with a name we'll try to duplicate
        Genre::factory()->create(['name' => 'Existing Genre']);
        
        // Test empty name validation
        $response = $this->post(route('genres.store'), [
            'name' => '',
            'description' => 'Test description',
        ]);
        
        $response->assertSessionHasErrors('name');
        
        // Test duplicate name validation
        $response = $this->post(route('genres.store'), [
            'name' => 'Existing Genre',
            'description' => 'Test description',
        ]);
        
        $response->assertSessionHasErrors('name');
    }

    /**
     * Test the genre edit form.
     */
    public function test_genre_edit_form()
    {
        $genre = Genre::factory()->create();
        
        $response = $this->get(route('genres.edit', $genre->id));
        
        $response->assertStatus(200);
        $response->assertViewIs('genres.edit');
        $response->assertViewHas('genre', $genre);
    }

    /**
     * Test updating a genre.
     */
    public function test_genre_update()
    {
        // Create a genre
        $genre = Genre::factory()->create();
        
        $updatedData = [
            'name' => 'Updated Genre Name',
            'description' => 'Updated genre description',
        ];
        
        $response = $this->put(route('genres.update', $genre->id), $updatedData);
        
        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success');
        
        // Check database for the updated genre
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'Updated Genre Name',
            'description' => 'Updated genre description',
        ]);
    }

    /**
     * Test validation for updating a genre.
     */
    public function test_genre_update_validation()
    {
        // Create two genres
        $genre1 = Genre::factory()->create(['name' => 'First Genre']);
        $genre2 = Genre::factory()->create(['name' => 'Second Genre']);
        
        // Test empty name validation
        $response = $this->put(route('genres.update', $genre1->id), [
            'name' => '',
            'description' => 'Test description',
        ]);
        
        $response->assertSessionHasErrors('name');
        
        // Test duplicate name validation (trying to rename genre1 to genre2's name)
        $response = $this->put(route('genres.update', $genre1->id), [
            'name' => 'Second Genre',
            'description' => 'Test description',
        ]);
        
        $response->assertSessionHasErrors('name');
    }

    /**
     * Test deleting a genre.
     */
    public function test_genre_delete()
    {
        // Create a genre
        $genre = Genre::factory()->create();
        
        $response = $this->delete(route('genres.destroy', $genre->id));
        
        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success');
        
        // Check that genre is removed from database
        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    /**
     * Test deleting a genre that has associated tracks.
     */
    public function test_genre_delete_with_tracks()
    {
        // Create a genre and associated tracks
        $genre = Genre::factory()->create();
        $track = Track::factory()->create();
        $track->genres()->attach($genre->id);
        
        $response = $this->delete(route('genres.destroy', $genre->id));
        
        // The genre should be deleted and the pivot relationship removed
        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success');
        
        // Check that genre is removed from database
        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
        
        // Check that the track still exists
        $this->assertDatabaseHas('tracks', [
            'id' => $track->id,
        ]);
        
        // Check that the genre-track relationship is removed
        $this->assertDatabaseMissing('genre_track', [
            'genre_id' => $genre->id,
            'track_id' => $track->id,
        ]);
    }

    /**
     * Test the show genre page.
     */
    public function test_genre_show()
    {
        $genre = Genre::factory()->create();
        
        // Create some tracks associated with this genre
        $tracks = Track::factory()->count(3)->create();
        foreach ($tracks as $track) {
            $track->genres()->attach($genre->id);
        }
        
        $response = $this->get(route('genres.show', $genre->id));
        
        $response->assertStatus(200);
        $response->assertViewIs('genres.show');
        $response->assertViewHas('genre', $genre);
        
        // Check that the view has tracks
        $this->assertEquals(3, $response->viewData('tracks')->count());
    }
} 