<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Track;
use App\Models\Genre;
use Illuminate\Database\Seeder;

final class TrackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding tracks...');

        // Ensure we have genres to attach to tracks
        $genres = Genre::all();
        if ($genres->isEmpty()) {
            $this->command->warn('No genres found. Running GenreSeeder first...');
            $this->call(GenreSeeder::class);
            $genres = Genre::all();
        }

        // Create tracks with different statuses
        $this->createTracksWithStatus('pending', 10, $genres);
        $this->createTracksWithStatus('processing', 5, $genres);
        $this->createTracksWithStatus('completed', 20, $genres);
        $this->createTracksWithStatus('failed', 3, $genres);
        $this->createTracksWithStatus('stopped', 2, $genres);

        // Create some tracks uploaded to YouTube
        $this->createYouTubeTracks(15, $genres);

        // Create some popular tracks
        $this->createPopularTracks(5, $genres);

        $this->command->info('Created sample tracks with various statuses.');
    }

    /**
     * Create tracks with specific status.
     */
    private function createTracksWithStatus(string $status, int $count, $genres): void
    {
        $tracks = Track::factory()
            ->count($count)
            ->state(['status' => $status])
            ->create();

        // Attach random genres to each track
        $tracks->each(function (Track $track) use ($genres) {
            $randomGenres = $genres->random(rand(1, 3));
            $track->genres()->attach($randomGenres->pluck('id'));
        });

        $this->command->info("Created {$count} tracks with status: {$status}");
    }

    /**
     * Create tracks uploaded to YouTube.
     */
    private function createYouTubeTracks(int $count, $genres): void
    {
        $tracks = Track::factory()
            ->count($count)
            ->uploadedToYoutube()
            ->create();

        // Attach random genres to each track
        $tracks->each(function (Track $track) use ($genres) {
            $randomGenres = $genres->random(rand(1, 2));
            $track->genres()->attach($randomGenres->pluck('id'));
        });

        $this->command->info("Created {$count} tracks uploaded to YouTube");
    }

    /**
     * Create popular tracks with high view counts.
     */
    private function createPopularTracks(int $count, $genres): void
    {
        $tracks = Track::factory()
            ->count($count)
            ->popular()
            ->create();

        // Attach popular genres to these tracks
        $popularGenres = $genres->whereIn('name', ['City Pop', 'Synthwave', 'Lo-Fi', 'Electronic']);
        
        $tracks->each(function (Track $track) use ($popularGenres) {
            $randomGenres = $popularGenres->random(min(2, $popularGenres->count()));
            $track->genres()->attach($randomGenres->pluck('id'));
        });

        $this->command->info("Created {$count} popular tracks with high view counts");
    }
}
