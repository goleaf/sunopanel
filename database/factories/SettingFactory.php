<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
final class SettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Setting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['string', 'boolean', 'integer', 'float', 'json']);
        
        return [
            'key' => $this->faker->unique()->slug(2, '_'),
            'value' => $this->getValueForType($type),
            'type' => $type,
            'description' => $this->faker->sentence(),
        ];
    }

    /**
     * Get appropriate value based on type.
     */
    private function getValueForType(string $type): string
    {
        return match ($type) {
            'boolean' => $this->faker->boolean() ? '1' : '0',
            'integer' => (string) $this->faker->numberBetween(1, 1000),
            'float' => (string) $this->faker->randomFloat(2, 0, 100),
            'json' => json_encode([
                'option1' => $this->faker->word(),
                'option2' => $this->faker->numberBetween(1, 100),
                'enabled' => $this->faker->boolean(),
            ]),
            default => $this->faker->sentence(),
        };
    }

    /**
     * Create a boolean setting.
     */
    public function boolean(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'boolean',
            'value' => $this->faker->boolean() ? '1' : '0',
        ]);
    }

    /**
     * Create a string setting.
     */
    public function string(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'string',
            'value' => $this->faker->sentence(),
        ]);
    }

    /**
     * Create an integer setting.
     */
    public function integer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'integer',
            'value' => (string) $this->faker->numberBetween(1, 1000),
        ]);
    }

    /**
     * Create a JSON setting.
     */
    public function json(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'json',
            'value' => json_encode([
                'enabled' => $this->faker->boolean(),
                'count' => $this->faker->numberBetween(1, 100),
                'options' => $this->faker->words(3),
            ]),
        ]);
    }

    /**
     * Create a YouTube visibility filter setting.
     */
    public function youtubeVisibilityFilter(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'youtube_visibility_filter',
            'value' => $this->faker->randomElement(['all', 'uploaded', 'not_uploaded']),
            'type' => 'string',
            'description' => 'Global filter for YouTube upload visibility',
        ]);
    }

    /**
     * Create a show YouTube column setting.
     */
    public function showYoutubeColumn(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'show_youtube_column',
            'value' => $this->faker->boolean() ? '1' : '0',
            'type' => 'boolean',
            'description' => 'Show YouTube column in track listings',
        ]);
    }

    /**
     * Create a setting with specific key and value.
     */
    public function withKeyValue(string $key, mixed $value, string $type = 'string'): static
    {
        $processedValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };

        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value' => $processedValue,
            'type' => $type,
        ]);
    }
}
