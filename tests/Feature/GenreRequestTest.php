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
        // Test missing required fields
        $response = $this->post(route('genres.store'), []);
        $response->assertSessionHasErrors(['name']);

        // Test valid data
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
        // Create a genre
        $genre = Genre::create([
            'name' => 'Original Genre',
            'description' => 'Original Description',
        ]);

        // Test valid data
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
        // Create a genre and track
        $genre = Genre::create([
            'name' => 'Test Genre',
            'description' => 'Test Description',
        ]);

        $track = Track::create([
            'title' => 'Test Track',
            'audio_url' => 'https://example.com/audio.mp3',
            'image_url' => 'https://example.com/image.jpg',
            'unique_id' => 'track1',
            'duration' => '3:00',
        ]);

        // Attach track to genre
        $genre->tracks()->attach($track->id);

        // Test deleting genre
        $response = $this->delete(route('genres.destroy', $genre->id));
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('genres.index'));
        
        // Check if genre was deleted
        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
        
        // Check if track was detached but not deleted
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
        // Create a genre
        Genre::create([
            'name' => 'Unique Genre',
            'description' => 'Description',
        ]);

        // Test creating a genre with the same name
        $response = $this->post(route('genres.store'), [
            'name' => 'Unique Genre',
            'description' => 'Another Description',
        ]);
        
        $response->assertSessionHasErrors(['name']);
        
        // Test updating a genre with a unique name
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