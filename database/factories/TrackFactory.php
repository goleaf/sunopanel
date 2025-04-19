<?php

namespace Database\Factories;

use App\Models\Track;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Track>
 */
class TrackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a random duration between 2:00 and 5:59
        $minutes = $this->faker->numberBetween(2, 5);
        $seconds = $this->faker->numberBetween(0, 59);
        $duration = sprintf('%d:%02d', $minutes, $seconds);
        
        return [
            'title' => $this->faker->sentence(3, false),
            'audio_url' => 'https://example.com/audio/' . $this->faker->uuid() . '.mp3',
            'image_url' => $this->faker->imageUrl(640, 480, 'music'),
            'unique_id' => md5($this->faker->sentence()),
            'duration' => $duration,
        ];
    }
    
    /**
     * Configure the model factory to have the track attached to genres.
     * This is a state method for use in tests when we need tracks with genres.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withGenres($count = 2)
    {
        return $this->afterCreating(function (Track $track) use ($count) {
            // Assign genres
            $track->assignGenres(implode(', ', $this->faker->randomElements(['Rock', 'Pop', 'Jazz', 'Hip Hop', 'Electronic'], $count)));
        });
    }
}
