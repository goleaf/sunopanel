<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\YouTubeAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\YouTubeAccount>
 */
final class YouTubeAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = YouTubeAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $channelName = $this->faker->company() . ' Music';
        
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'channel_id' => 'UC' . $this->faker->regexify('[a-zA-Z0-9_-]{22}'),
            'channel_name' => $channelName,
            'access_token' => $this->faker->sha256(),
            'refresh_token' => $this->faker->sha256(),
            'token_expires_at' => $this->faker->dateTimeBetween('now', '+1 hour'),
            'account_info' => [
                'subscriber_count' => $this->faker->numberBetween(100, 100000),
                'video_count' => $this->faker->numberBetween(10, 1000),
                'view_count' => $this->faker->numberBetween(1000, 1000000),
                'description' => $this->faker->paragraph(),
                'country' => $this->faker->countryCode(),
                'custom_url' => '@' . $this->faker->slug(),
            ],
            'is_active' => false, // Only one should be active
        ];
    }

    /**
     * Create an active YouTube account.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create a YouTube account with expired token.
     */
    public function expiredToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'token_expires_at' => $this->faker->dateTimeBetween('-1 week', '-1 hour'),
        ]);
    }

    /**
     * Create a YouTube account with valid token.
     */
    public function validToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'token_expires_at' => $this->faker->dateTimeBetween('+1 hour', '+1 week'),
        ]);
    }

    /**
     * Create a YouTube account without tokens.
     */
    public function withoutTokens(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
        ]);
    }

    /**
     * Create a popular YouTube channel.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_info' => [
                'subscriber_count' => $this->faker->numberBetween(100000, 10000000),
                'video_count' => $this->faker->numberBetween(500, 5000),
                'view_count' => $this->faker->numberBetween(10000000, 1000000000),
                'description' => $this->faker->paragraph(),
                'country' => $this->faker->countryCode(),
                'custom_url' => '@' . $this->faker->slug(),
                'verified' => true,
            ],
        ]);
    }

    /**
     * Create a YouTube account with specific channel name.
     */
    public function withChannelName(string $channelName): static
    {
        return $this->state(fn (array $attributes) => [
            'channel_name' => $channelName,
        ]);
    }
}
