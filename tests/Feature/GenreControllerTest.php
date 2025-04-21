<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GenreControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    
    
    public function test_index_page_displays_genres(): void
    {
        $genres = Genre::factory()->count(3)->create();

        $response = $this->get('/genres');

        $response->assertStatus(200);
        $response->assertViewIs('genres.index');
        $response->assertViewHas('genres');
        foreach ($genres as $genre) {
            $response->assertSee($genre->name);
        }
    }

    
    
    public function test_create_page_loads(): void
    {
        $response = $this->get('/genres/create');

        $response->assertStatus(200);
        $response->assertViewIs('genres.form');
    }

    
    
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

    
    
    public function test_show_genre(): void
    {
        $genre = Genre::factory()->create();
        $tracks = Track::factory()->count(3)->create();
        foreach ($tracks as $track) {
            $track->genres()->attach($genre->id);
        }

        $response = $this->get("/genres/{$genre->id}");

        $response->assertStatus(200);
        $response->assertViewIs('genres.show');
        $response->assertViewHas('genre');
        $response->assertSee($genre->name);
        foreach ($tracks as $track) {
            $response->assertSee($track->name);
        }
    }

    
    
    public function test_edit_genre(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->get("/genres/{$genre->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('genres.form');
        $response->assertViewHas('genre');
        $response->assertSee($genre->name);
    }

    
    
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

    
    
    public function test_delete_genre(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->delete("/genres/{$genre->id}");

        $response->assertRedirect('/genres');
        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    
    
    public function test_find_or_create_by_name(): void
    {
        $existingGenre = Genre::factory()->create(['name' => 'Existing Genre']);
        $foundGenre = Genre::findOrCreateByName('Existing Genre');
        $this->assertEquals($existingGenre->id, $foundGenre->id);
        $newGenre = Genre::findOrCreateByName('New Genre');
        $this->assertDatabaseHas('genres', [
            'name' => 'New Genre',
        ]);
    }

    
    
    public function test_genres_index_page(): void {
        Genre::factory()->count(5)->create();

        $response = $this->get(route('genres.index'));

        $response->assertStatus(200);
        $response->assertViewIs('genres.index');
        $response->assertViewHas('genres');
    }

    
    
    public function test_genre_create_form(): void {
        $response = $this->get(route('genres.create'));

        $response->assertStatus(200);
        $response->assertViewIs('genres.form');
    }

    
    
    public function test_genre_store(): void {
        $genreData = [
            'name' => 'Test Genre',
            'description' => 'This is a test genre description',
        ];

        $response = $this->post(route('genres.store'), $genreData);

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('genres', [
            'name' => 'Test Genre',
            'description' => 'This is a test genre description',
        ]);
    }

    
    
    public function test_genre_store_validation(): void {
        Genre::factory()->create(['name' => 'Existing Genre']);
        $response = $this->post(route('genres.store'), [
            'name' => '',
            'description' => 'Test description',
        ]);

        $response->assertSessionHasErrors('name');
        $response = $this->post(route('genres.store'), [
            'name' => 'Existing Genre',
            'description' => 'Test description',
        ]);

        $response->assertSessionHasErrors('name');
    }

    
    
    public function test_genre_edit_form(): void {
        $genre = Genre::factory()->create();

        $response = $this->get(route('genres.edit', $genre->id));

        $response->assertStatus(200);
        $response->assertViewIs('genres.form');
        $response->assertViewHas('genre', $genre);
    }

    
    
    public function test_genre_update(): void {
        $genre = Genre::factory()->create();

        $updatedData = [
            'name' => 'Updated Genre Name',
            'description' => 'Updated genre description',
        ];

        $response = $this->put(route('genres.update', $genre->id), $updatedData);

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'Updated Genre Name',
            'description' => 'Updated genre description',
        ]);
    }

    
    
    public function test_genre_update_validation(): void {
        $genre1 = Genre::factory()->create(['name' => 'First Genre']);
        $genre2 = Genre::factory()->create(['name' => 'Second Genre']);
        $response = $this->put(route('genres.update', $genre1->id), [
            'name' => '',
            'description' => 'Test description',
        ]);

        $response->assertSessionHasErrors('name');
        $response = $this->put(route('genres.update', $genre1->id), [
            'name' => 'Second Genre',
            'description' => 'Test description',
        ]);

        $response->assertSessionHasErrors('name');
    }

    
    
    public function test_genre_delete(): void {
        $genre = Genre::factory()->create();

        $response = $this->delete(route('genres.destroy', $genre->id));

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    
    
    public function test_genre_delete_with_tracks(): void {
        $genre = Genre::factory()->create();
        $track = Track::factory()->create();
        $track->genres()->attach($genre->id);

        $response = $this->delete(route('genres.destroy', $genre->id));
        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
        $this->assertDatabaseHas('tracks', [
            'id' => $track->id,
        ]);
        $this->assertDatabaseMissing('genre_track', [
            'genre_id' => $genre->id,
            'track_id' => $track->id,
        ]);
    }

    
    
    public function test_genre_show(): void {
        $genre = Genre::factory()->create();
        $tracks = Track::factory()->count(3)->create();
        foreach ($tracks as $track) {
            $track->genres()->attach($genre->id);
        }

        $response = $this->get(route('genres.show', $genre->id));

        $response->assertStatus(200);
        $response->assertViewIs('genres.show');
        $response->assertViewHas('genre', $genre);
        $this->assertEquals(3, $response->viewData('tracks')->count());
    }
}
