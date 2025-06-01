<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\YouTubeCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\YouTubeCredential>
 */
final class YouTubeCredentialFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = YouTubeCredential::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $useOAuth = $this->faker->boolean(70); // 70% chance of OAuth
        
        return [
            'client_id' => $useOAuth ? $this->faker->regexify('[0-9]{12}-[a-z0-9]{32}\.apps\.googleusercontent\.com') : null,
            'client_secret' => $useOAuth ? $this->faker->regexify('GOCSPX-[a-zA-Z0-9_-]{28}') : null,
            'redirect_uri' => $useOAuth ? $this->faker->url() . '/oauth/callback' : null,
            'access_token' => $useOAuth ? $this->faker->sha256() : null,
            'refresh_token' => $useOAuth ? $this->faker->sha256() : null,
            'token_created_at' => $useOAuth ? time() - $this->faker->numberBetween(0, 3600) : null,
            'token_expires_in' => $useOAuth ? 3600 : null, // 1 hour expiry
            'use_oauth' => $useOAuth,
            'user_email' => $useOAuth ? $this->faker->email() : null,
        ];
    }

    /**
     * Create OAuth credentials.
     */
    public function oauth(): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $this->faker->regexify('[0-9]{12}-[a-z0-9]{32}\.apps\.googleusercontent\.com'),
            'client_secret' => $this->faker->regexify('GOCSPX-[a-zA-Z0-9_-]{28}'),
            'redirect_uri' => $this->faker->url() . '/oauth/callback',
            'access_token' => $this->faker->sha256(),
            'refresh_token' => $this->faker->sha256(),
            'token_created_at' => time() - $this->faker->numberBetween(0, 3600),
            'token_expires_in' => 3600,
            'use_oauth' => true,
            'user_email' => $this->faker->email(),
        ]);
    }

    /**
     * Create non-OAuth credentials.
     */
    public function nonOAuth(): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => null,
            'client_secret' => null,
            'redirect_uri' => null,
            'access_token' => null,
            'refresh_token' => null,
            'token_created_at' => null,
            'token_expires_in' => null,
            'use_oauth' => false,
            'user_email' => null,
        ]);
    }

    /**
     * Create credentials with expired token.
     */
    public function expiredToken(): static
    {
        return $this->oauth()->state(fn (array $attributes) => [
            'token_created_at' => time() - 7200, // 2 hours ago (tokens usually expire in 1 hour)
        ]);
    }

    /**
     * Create credentials with fresh token.
     */
    public function freshToken(): static
    {
        return $this->oauth()->state(fn (array $attributes) => [
            'token_created_at' => time() - 300, // 5 minutes ago
        ]);
    }

    /**
     * Create credentials without tokens (OAuth setup incomplete).
     */
    public function withoutTokens(): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $this->faker->regexify('[0-9]{12}-[a-z0-9]{32}\.apps\.googleusercontent\.com'),
            'client_secret' => $this->faker->regexify('GOCSPX-[a-zA-Z0-9_-]{28}'),
            'redirect_uri' => $this->faker->url() . '/oauth/callback',
            'access_token' => null,
            'refresh_token' => null,
            'token_created_at' => null,
            'use_oauth' => true,
            'api_key' => null,
        ]);
    }

    /**
     * Create credentials with specific redirect URI.
     */
    public function withRedirectUri(string $redirectUri): static
    {
        return $this->oauth()->state(fn (array $attributes) => [
            'redirect_uri' => $redirectUri,
        ]);
    }
}
