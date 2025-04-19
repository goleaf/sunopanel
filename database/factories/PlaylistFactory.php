<?php

namespace Database\Factories;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Playlist>
 */
class PlaylistFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Ensure a genre exists for the relationship
        $genre = Genre::first() ?? Genre::factory()->create();

        return [
            'name' => $this->faker->words(3, true).' Playlist',
            'description' => $this->faker->paragraph(),
            'cover_image' => $this->faker->imageUrl(640, 480, 'music', true),
            'genre_id' => $genre->id,
        ];
    }
}
