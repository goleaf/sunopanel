<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');

        // Seed essential application settings first
        $this->call([
            SettingSeeder::class,
        ]);

        // Seed genres before tracks (tracks depend on genres)
        $this->call([
            GenreSeeder::class,
        ]);

        // Seed tracks with relationships to genres
        $this->call([
            TrackSeeder::class,
        ]);

        // Seed YouTube test data if needed
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                TestYouTubeAccountSeeder::class,
            ]);
        }

        $this->command->info('✅ Database seeding completed successfully!');
    }
}
