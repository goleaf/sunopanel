<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Track;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Track>
 */
final class TrackFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Track::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = Track::$statuses;
        $status = $statuses[array_rand($statuses)];
        $hasYouTubeUpload = $this->faker->boolean(30); // 30% chance of YouTube upload
        
        return [
            'title' => $this->faker->words(3, true),
            'suno_id' => $this->faker->uuid(),
            'mp3_url' => 'https://cdn1.suno.ai/' . $this->faker->uuid() . '.mp3',
            'image_url' => 'https://cdn2.suno.ai/image_' . $this->faker->uuid() . '.jpeg',
            'mp3_path' => 'mp3/' . $this->faker->uuid() . '.mp3',
            'image_path' => 'images/' . $this->faker->uuid() . '.jpg',
            'mp4_path' => $this->faker->boolean(50) ? 'videos/' . $this->faker->uuid() . '.mp4' : null,
            'genres_string' => ['city pop', 'synthwave', 'lo-fi', 'jazz', 'electronic', 'ambient', 'rock', 'pop', 'classical', 'hip hop'][array_rand(['city pop', 'synthwave', 'lo-fi', 'jazz', 'electronic', 'ambient', 'rock', 'pop', 'classical', 'hip hop'])],
            'status' => $status,
            'progress' => $this->getProgressForStatus($status),
            'error_message' => $status === 'failed' ? $this->faker->sentence() : null,
            'youtube_video_id' => $hasYouTubeUpload ? $this->faker->regexify('[a-zA-Z0-9_-]{11}') : null,
            'youtube_playlist_id' => $hasYouTubeUpload && $this->faker->boolean(20) ? $this->faker->regexify('PL[a-zA-Z0-9_-]{32}') : null,
            'youtube_uploaded_at' => $hasYouTubeUpload ? $this->faker->dateTimeBetween('-1 year', 'now') : null,
            'youtube_views' => $hasYouTubeUpload ? $this->faker->numberBetween(0, 100000) : null,
            'youtube_stats_updated_at' => $hasYouTubeUpload ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
            'youtube_enabled' => $this->faker->boolean(80), // 80% enabled by default
        ];
    }

    /**
     * Get appropriate progress value based on status.
     */
    private function getProgressForStatus(string $status): int
    {
        return match ($status) {
            'pending' => 0,
            'processing' => $this->faker->numberBetween(1, 99),
            'completed' => 100,
            'failed' => $this->faker->numberBetween(0, 90),
            'stopped' => $this->faker->numberBetween(0, 90),
            default => 0,
        };
    }

    /**
     * Create a track with pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'progress' => 0,
            'error_message' => null,
            'youtube_video_id' => null,
            'youtube_playlist_id' => null,
            'youtube_uploaded_at' => null,
            'youtube_views' => null,
            'youtube_stats_updated_at' => null,
        ]);
    }

    /**
     * Create a track with processing status.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'progress' => $this->faker->numberBetween(1, 99),
            'error_message' => null,
            'youtube_video_id' => null,
            'youtube_playlist_id' => null,
            'youtube_uploaded_at' => null,
            'youtube_views' => null,
            'youtube_stats_updated_at' => null,
        ]);
    }

    /**
     * Create a track with completed status.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress' => 100,
            'error_message' => null,
        ]);
    }

    /**
     * Create a track with failed status.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'progress' => $this->faker->numberBetween(0, 90),
            'error_message' => ['Download failed: Connection timeout', 'Invalid audio format', 'File size too large', 'Processing timeout', 'Network error occurred'][array_rand(['Download failed: Connection timeout', 'Invalid audio format', 'File size too large', 'Processing timeout', 'Network error occurred'])],
            'youtube_video_id' => null,
            'youtube_playlist_id' => null,
            'youtube_uploaded_at' => null,
            'youtube_views' => null,
            'youtube_stats_updated_at' => null,
        ]);
    }

    /**
     * Create a track with stopped status.
     */
    public function stopped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'stopped',
            'progress' => $this->faker->numberBetween(0, 90),
            'error_message' => null,
            'youtube_video_id' => null,
            'youtube_playlist_id' => null,
            'youtube_uploaded_at' => null,
            'youtube_views' => null,
            'youtube_stats_updated_at' => null,
        ]);
    }

    /**
     * Create a track that has been uploaded to YouTube.
     */
    public function uploadedToYoutube(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress' => 100,
            'youtube_video_id' => $this->faker->regexify('[a-zA-Z0-9_-]{11}'),
            'youtube_playlist_id' => $this->faker->boolean(30) ? $this->faker->regexify('PL[a-zA-Z0-9_-]{32}') : null,
            'youtube_uploaded_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'youtube_views' => $this->faker->numberBetween(0, 100000),
            'youtube_stats_updated_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'youtube_enabled' => true,
        ]);
    }

    /**
     * Create a track with high YouTube views.
     */
    public function popular(): static
    {
        return $this->uploadedToYoutube()->state(fn (array $attributes) => [
            'youtube_views' => $this->faker->numberBetween(10000, 1000000),
        ]);
    }

    /**
     * Create a track with specific genre.
     */
    public function withGenre(string $genre): static
    {
        return $this->state(fn (array $attributes) => [
            'genres_string' => $genre,
        ]);
    }

    /**
     * Create a track with city pop genre.
     */
    public function cityPop(): static
    {
        return $this->withGenre('city pop');
    }

    /**
     * Create a track with synthwave genre.
     */
    public function synthwave(): static
    {
        return $this->withGenre('synthwave');
    }

    /**
     * Create a track with lo-fi genre.
     */
    public function lofi(): static
    {
        return $this->withGenre('lo-fi');
    }
}
