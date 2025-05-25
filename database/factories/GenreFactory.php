<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Genre>
 */
final class GenreFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Genre::class;

    /**
     * List of realistic music genres.
     */
    private static array $genres = [
        'City Pop',
        'Synthwave',
        'Lo-Fi',
        'Jazz',
        'Electronic',
        'Ambient',
        'Rock',
        'Pop',
        'Classical',
        'Hip Hop',
        'R&B',
        'Funk',
        'Soul',
        'Blues',
        'Country',
        'Folk',
        'Indie',
        'Alternative',
        'Punk',
        'Metal',
        'Reggae',
        'Ska',
        'Disco',
        'House',
        'Techno',
        'Trance',
        'Drum & Bass',
        'Dubstep',
        'Trap',
        'Vaporwave',
        'Chillwave',
        'New Age',
        'World Music',
        'Latin',
        'Bossa Nova',
        'Swing',
        'Bebop',
        'Fusion',
        'Progressive',
        'Experimental',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(self::$genres);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'genre_id' => $this->faker->unique()->numberBetween(1, 1000),
        ];
    }

    /**
     * Create a city pop genre.
     */
    public function cityPop(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'City Pop',
            'slug' => 'city-pop',
        ]);
    }

    /**
     * Create a synthwave genre.
     */
    public function synthwave(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Synthwave',
            'slug' => 'synthwave',
        ]);
    }

    /**
     * Create a lo-fi genre.
     */
    public function lofi(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Lo-Fi',
            'slug' => 'lo-fi',
        ]);
    }

    /**
     * Create a jazz genre.
     */
    public function jazz(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Jazz',
            'slug' => 'jazz',
        ]);
    }

    /**
     * Create an electronic genre.
     */
    public function electronic(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Electronic',
            'slug' => 'electronic',
        ]);
    }

    /**
     * Create a genre with a specific name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }
}
