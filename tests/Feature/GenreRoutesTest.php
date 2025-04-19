<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $genre = Genre::factory()->create(['name' => 'Bubblegum bass']);
        $track = Track::factory()->create([
            'title' => 'Test Track',
            'audio_url' => 'https:
            'image_url' => 'https:
        ]);

        $track->genres()->attach($genre->id);
    }

    #[Test]
public function genres_index_page_loads_correctly()
    {
        $response = $this->get(route('genres.index'));

        $response->assertStatus(200);
        $response->assertSee('Bubblegum bass');
        $response->assertViewIs('genres.index');
    }

    #[Test]
public function genre_show_page_loads_correctly()
    {
        $genre = Genre::first();

        $response = $this->get(route('genres.show', $genre));

        $response->assertStatus(200);
        $response->assertSee($genre->name);
        $response->assertSee('Test Track');
        $response->assertViewIs('genres.show');
    }

    #[Test]
public function genre_create_page_loads_correctly()
    {
        $response = $this->get(route('genres.create'));

        $response->assertStatus(200);
        $response->assertSee('Add New Genre');
        $response->assertViewIs('genres.create');
    }

    #[Test]
public function genre_edit_page_loads_correctly()
    {
        $genre = Genre::first();

        $response = $this->get(route('genres.edit', $genre));

        $response->assertStatus(200);
        $response->assertSee($genre->name);
        $response->assertViewIs('genres.edit');
    }

    #[Test]
public function genre_can_be_created()
    {
        $response = $this->post(route('genres.store'), [
            'name' => 'Rock',
            'description' => 'Rock music genre',
        ]);

        $response->assertRedirect(route('genres.index'));
        $this->assertDatabaseHas('genres', ['name' => 'Rock']);
    }

    #[Test]
public function genre_can_be_updated()
    {
        $genre = Genre::first();

        $response = $this->put(route('genres.update', $genre), [
            'name' => 'Updated Genre',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('genres.index'));
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'Updated Genre',
            'description' => 'Updated description',
        ]);
    }

    #[Test]
public function genre_can_be_deleted()
    {
        $genre = Genre::first();

        $response = $this->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));
        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }

    #[Test]
public function genres_can_be_searched()
    {
        Genre::factory()->create(['name' => 'Rock']);

        $response = $this->get(route('genres.index', ['search' => 'bubblegum']));

        $response->assertStatus(200);
        $response->assertSee('Bubblegum bass');
        $response->assertDontSee('Rock');
    }

    #[Test]
public function genre_shows_associated_tracks()
    {
        $genre = Genre::first();
        $track2 = Track::factory()->create([
            'title' => 'Another Test Track',
            'audio_url' => 'https:
            'image_url' => 'https:
        ]);
        $track2->genres()->attach($genre->id);

        $response = $this->get(route('genres.show', $genre));

        $response->assertStatus(200);
        $response->assertSee('Test Track');
        $response->assertSee('Another Test Track');
    }
}
