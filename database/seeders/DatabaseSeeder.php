<?php

namespace Database\Seeders;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Check if we only want to run specific seeders
        if ($this->command->option('class') === 'Database\\Seeders\\TrackSeeder') {
            $this->call(TrackSeeder::class);
            $this->command->info('Only ran the TrackSeeder as requested.');

            return;
        } elseif ($this->command->option('class') === 'Database\\Seeders\\PlaylistSeeder') {
            $this->call(PlaylistSeeder::class);
            $this->command->info('Only ran the PlaylistSeeder as requested.');

            return;
        }

        // Run the Track seeder first as it's required for playlists
        $this->call(TrackSeeder::class);

        // Create a test track explicitly for tests
        $testTrack = Track::create([
            'title' => 'Test Track',
            'audio_url' => 'https://example.com/test-track.mp3',
            'image_url' => 'https://example.com/test-track.jpg',
            'unique_id' => md5('Test Track'.time()),
            'duration' => '3:45',
        ]);

        // Ensure we have a Bubblegum bass genre
        $bubblegumGenre = Genre::firstOrCreate(['name' => 'Bubblegum bass']);
        $testTrack->genres()->attach($bubblegumGenre);

        // Create a test playlist
        $testPlaylist = Playlist::create([
            'name' => 'Test Playlist',
            'description' => 'A playlist for testing purposes',
        ]);

        // Add the test track to the playlist
        $testPlaylist->tracks()->attach($testTrack, ['position' => 1]);

        // Then run the Playlist seeder that depends on tracks and genres
        $this->call(PlaylistSeeder::class);
    }
}
