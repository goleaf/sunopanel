<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_genre_store_validation()
    {
        $response = $this->post(route('genres.store'), []);
        $response->assertSessionHasErrors(['name']);
        $validData = [
            'name' => 'Test Genre',
            'description' => 'Test Description',
        ];

        $response = $this->post(route('genres.store'), $validData);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'name' => 'Test Genre',
            'description' => 'Test Description',
        ]);
    }

    public function test_genre_update_validation()
    {
        $genre = Genre::create([
            'name' => 'Original Genre',
            'description' => 'Original Description',
        ]);
        $validData = [
            'name' => 'Updated Genre',
            'description' => 'Updated Description',
        ];

        $response = $this->put(route('genres.update', $genre->id), $validData);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'Updated Genre',
            'description' => 'Updated Description',
        ]);
    }

    public function test_genre_delete_validation()
    {
        $genre = Genre::create([
            'name' => 'Test Genre',
            'description' => 'Test Description',
        ]);

        $track = Track::create([
            'title' => 'Test Track',
            'audio_url' => 'https:
            'image_url' => 'https:
            'unique_id' => 'track1',
            'duration' => '3:00',
        ]);
        $genre->tracks()->attach($track->id);
        $response = $this->delete(route('genres.destroy', $genre->id));
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('genres.index'));
        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
        $this->assertDatabaseMissing('genre_track', [
            'genre_id' => $genre->id,
            'track_id' => $track->id,
        ]);

        $this->assertDatabaseHas('tracks', [
            'id' => $track->id,
        ]);
    }

    public function test_genre_name_uniqueness()
    {
        Genre::create([
            'name' => 'Unique Genre',
            'description' => 'Description',
        ]);
        $response = $this->post(route('genres.store'), [
            'name' => 'Unique Genre',
            'description' => 'Another Description',
        ]);

        $response->assertSessionHasErrors(['name']);
        $genre2 = Genre::create([
            'name' => 'Another Genre',
            'description' => 'Description',
        ]);

        $response = $this->put(route('genres.update', $genre2->id), [
            'name' => 'Unique Genre',
            'description' => 'Updated Description',
        ]);

        $response->assertSessionHasErrors(['name']);
    }
}
