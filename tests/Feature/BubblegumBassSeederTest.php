<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Track;
use Database\Seeders\TrackSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BubblegumBassSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_tracks_and_genres()
    {
        $this->seed(TrackSeeder::class);
        $this->assertDatabaseCount('tracks', 20);
        $bubblegumGenre = Genre::where('name', 'Bubblegum bass')->first();
        $this->assertNotNull($bubblegumGenre, 'Bubblegum bass genre should exist');
        $tracksWithGenre = $bubblegumGenre->tracks->count();
        $this->assertEquals(20, $tracksWithGenre, 'All tracks should be related to the Bubblegum bass genre');
        $this->assertDatabaseHas('tracks', [
            'title' => 'Neon Threads',
        ]);
        $this->assertDatabaseHas('genres', [
            'name' => 'Symphonic metal',
        ]);
    }

    public function test_seed_command_works()
    {
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\TrackSeeder'])
            ->assertSuccessful();
        $this->assertDatabaseCount('tracks', 20);
    }

    public function test_bubblegum_bass_is_one_genre()
    {
        Log::info('Running test_bubblegum_bass_is_one_genre');
        $genreVariations = [
            'bubblegum bass',
            'bubblegum-bass',
            'BUBBLEGUM BASS',
            'BuBbLeGuM bAsS',
            'bubblegum  bass',
            'bubblegumbass',
        ];
        foreach ($genreVariations as $index => $variant) {
            $track = Track::create([
                'title' => "Test Track {$index}",
                'audio_url' => 'https:
                'image_url' => 'https:
                'unique_id' => "test-track-{$index}",
            ]);
            $track->assignGenres($variant);

            Log::info("Created test track with genre variant: {$variant}");
        }
        $genreCount = Genre::where('name', 'Bubblegum bass')->count();
        $this->assertEquals(1, $genreCount, 'Expected only one "Bubblegum bass" genre');
        $this->assertEquals(0, Genre::where('name', 'bubblegum bass')->count(), 'Should not have created a lowercase "bubblegum bass" genre');
        $this->assertEquals(0, Genre::where('name', 'Bubblegum')->count(), 'Should not have created a separate "Bubblegum" genre');
        $this->assertEquals(0, Genre::where('name', 'Bass')->count(), 'Should not have created a separate "Bass" genre');
        foreach (Track::all() as $track) {
            $genres = $track->genres()->pluck('name')->toArray();
            $this->assertContains('Bubblegum bass', $genres, 'Track should have "Bubblegum bass" genre with correct capitalization');
            $this->assertEquals(1, count($genres), 'Track should only have one genre');
        }

        Log::info('Completed test_bubblegum_bass_is_one_genre');
    }

    public function test_genre_capitalization()
    {
        Log::info('Running test_genre_capitalization');
        $testCases = [
            'techno' => 'Techno',
            'house' => 'House',
            'drum and bass' => 'Drum and bass',
            'edm' => 'EDM',
            'bubblegum bass,rock,pop' => ['Bubblegum bass', 'Rock', 'Pop'],
        ];
        foreach ($testCases as $input => $expected) {
            $track = Track::create([
                'title' => "Track with {$input}",
                'audio_url' => 'https:
                'image_url' => 'https:
                'unique_id' => md5($input.time()),
            ]);
            $track->assignGenres($input);
            Log::info("Created track with genre input: {$input}");
            $genres = $track->genres()->pluck('name')->toArray();

            if (is_array($expected)) {
                foreach ($expected as $exp) {
                    $this->assertContains($exp, $genres, "Should contain genre '{$exp}' with correct capitalization");
                }
                $this->assertEquals(count($expected), count($genres), 'Should have exactly '.count($expected).' genres');
            } else {
                $this->assertContains($expected, $genres, "Should contain genre '{$expected}' with correct capitalization");
                $this->assertEquals(1, count($genres), 'Should have exactly 1 genre');
            }
        }

        Log::info('Completed test_genre_capitalization');
    }
}
