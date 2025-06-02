<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Factories\Factory;

final class WebhookLogFactory extends Factory
{
    protected $model = WebhookLog::class;

    public function definition(): array
    {
        return [
            'source' => $this->faker->randomElement(['youtube', 'suno', 'generic']),
            'event_type' => $this->faker->randomElement(['upload_complete', 'video_published', 'track_created', 'status_update']),
            'payload' => [
                'id' => $this->faker->uuid(),
                'title' => $this->faker->sentence(),
                'status' => $this->faker->randomElement(['success', 'pending', 'failed']),
                'timestamp' => now()->toISOString(),
                'data' => [
                    'key1' => $this->faker->word(),
                    'key2' => $this->faker->numberBetween(1, 1000),
                    'key3' => $this->faker->boolean(),
                ]
            ],
            'headers' => [
                'content-type' => 'application/json',
                'user-agent' => $this->faker->userAgent(),
                'x-webhook-signature' => $this->faker->sha256(),
                'x-timestamp' => (string) time(),
            ],
            'status' => $this->faker->randomElement(['pending', 'processing', 'processed', 'failed']),
            'response_status' => $this->faker->randomElement([200, 201, 400, 401, 404, 500]),
            'response_body' => $this->faker->randomElement([
                '{"success": true}',
                '{"error": "Invalid payload"}',
                '{"message": "Processed successfully"}',
                null
            ]),
            'processing_time' => $this->faker->numberBetween(50, 5000), // milliseconds
            'error_message' => $this->faker->randomElement([
                null,
                'Invalid JSON payload',
                'Authentication failed',
                'Processing timeout',
                'Unknown error occurred'
            ]),
            'retry_count' => $this->faker->numberBetween(0, 3),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => 'pending',
            'response_status' => null,
            'response_body' => null,
            'processing_time' => null,
        ]);
    }

    public function processing(): static
    {
        return $this->state([
            'status' => 'processing',
            'response_status' => null,
            'response_body' => null,
            'processing_time' => null,
        ]);
    }

    public function processed(): static
    {
        return $this->state([
            'status' => 'processed',
            'response_status' => 200,
            'response_body' => '{"success": true}',
            'processing_time' => $this->faker->numberBetween(100, 2000),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => 'failed',
            'response_status' => $this->faker->randomElement([400, 401, 404, 500]),
            'response_body' => '{"error": "Processing failed"}',
            'processing_time' => $this->faker->numberBetween(50, 1000),
            'error_message' => $this->faker->randomElement([
                'Invalid JSON payload',
                'Authentication failed',
                'Processing timeout',
                'Database error'
            ]),
        ]);
    }

    public function youtube(): static
    {
        return $this->state([
            'source' => 'youtube',
            'event_type' => $this->faker->randomElement(['upload_complete', 'video_published', 'analytics_update']),
            'payload' => [
                'video_id' => $this->faker->regexify('[a-zA-Z0-9_-]{11}'),
                'channel_id' => $this->faker->regexify('UC[a-zA-Z0-9_-]{22}'),
                'title' => $this->faker->sentence(),
                'status' => $this->faker->randomElement(['uploaded', 'processing', 'live']),
                'privacy_status' => $this->faker->randomElement(['public', 'unlisted', 'private']),
            ],
        ]);
    }

    public function suno(): static
    {
        return $this->state([
            'source' => 'suno',
            'event_type' => $this->faker->randomElement(['track_created', 'track_updated', 'processing_complete']),
            'payload' => [
                'track_id' => $this->faker->uuid(),
                'suno_id' => $this->faker->regexify('[a-f0-9-]{36}'),
                'title' => $this->faker->sentence(),
                'artist' => $this->faker->name(),
                'genre' => $this->faker->randomElement(['City Pop', 'Lo-Fi', 'Synthwave', 'Jazz']),
                'status' => $this->faker->randomElement(['completed', 'processing', 'failed']),
            ],
        ]);
    }
} 