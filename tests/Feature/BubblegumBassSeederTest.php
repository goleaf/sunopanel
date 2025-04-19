<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use App\Models\Track;
use App\Models\Genre;
use Database\Seeders\TrackSeeder;

class BubblegumBassSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the seeder functionality
     */
    public function test_seeder_creates_tracks_and_genres()
    {
        // Run the seeder
        $this->seed(TrackSeeder::class);
        
        // Check that we have tracks
        $this->assertDatabaseCount('tracks', 20);
        
        // Check that all tracks have the "Bubblegum bass" genre
        $bubblegumGenre = Genre::where('name', 'Bubblegum bass')->first();
        $this->assertNotNull($bubblegumGenre, 'Bubblegum bass genre should exist');
        
        // Check that all tracks are related to the genre
        $tracksWithGenre = $bubblegumGenre->tracks->count();
        $this->assertEquals(20, $tracksWithGenre, 'All tracks should be related to the Bubblegum bass genre');
        
        // Check that some specific tracks exist
        $this->assertDatabaseHas('tracks', [
            'title' => 'Neon Threads'
        ]);
        
        // Check that our specific genres are created and properly formatted (capitalized)
        $this->assertDatabaseHas('genres', [
            'name' => 'Symphonic metal'
        ]);
    }
    
    /**
     * Test that our command works correctly
     */
    public function test_seed_command_works()
    {
        // Execute the command using the TrackSeeder
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\TrackSeeder'])
            ->assertSuccessful();
            
        // Check data was created
        $this->assertDatabaseCount('tracks', 20);
    }

    /**
     * Test that bubblegum bass is correctly treated as one genre.
     */
    public function test_bubblegum_bass_is_one_genre()
    {
        // Log test execution
        Log::info('Running test_bubblegum_bass_is_one_genre');
        
        // Setup test data
        $genreVariations = [
            'bubblegum bass',
            'bubblegum-bass',
            'BUBBLEGUM BASS',
            'BuBbLeGuM bAsS',
            'bubblegum  bass', // double space
        ];
        
        // Create test tracks with various forms of "bubblegum bass"
        foreach ($genreVariations as $index => $variant) {
            $track = Track::create([
                'title' => "Test Track {$index}",
                'audio_url' => 'https://example.com/audio.mp3',
                'image_url' => 'https://example.com/image.jpg',
                'unique_id' => "test-track-{$index}"
            ]);
            
            // Assign the variant genre
            $track->assignGenres($variant);
            
            Log::info("Created test track with genre variant: {$variant}");
        }
        
        // Verify only one "Bubblegum bass" genre was created
        $genreCount = Genre::where('name', 'Bubblegum bass')->count();
        $this->assertEquals(1, $genreCount, 'Expected only one "Bubblegum bass" genre');
        
        // Verify no separate "bubblegum" or "bass" genres were created
        $this->assertEquals(0, Genre::where('name', 'Bubblegum')->count(), 'Should not have created a separate "Bubblegum" genre');
        $this->assertEquals(0, Genre::where('name', 'Bass')->count(), 'Should not have created a separate "Bass" genre');
        
        // Verify all tracks have the correct genre
        foreach (Track::all() as $track) {
            $genres = $track->genres()->pluck('name')->toArray();
            $this->assertContains('Bubblegum bass', $genres, 'Track should have "Bubblegum bass" genre');
            $this->assertEquals(1, count($genres), 'Track should only have one genre');
        }
        
        Log::info('Completed test_bubblegum_bass_is_one_genre');
    }
    
    /**
     * Test that genres are properly capitalized.
     */
    public function test_genre_capitalization()
    {
        // Log test execution
        Log::info('Running test_genre_capitalization');
        
        // Test data with lowercase genres that should be capitalized
        $testCases = [
            'techno' => 'Techno',
            'house' => 'House', 
            'drum and bass' => 'Drum and bass',
            'edm' => 'Edm',
            'bubblegum bass,rock,pop' => ['Bubblegum bass', 'Rock', 'Pop']
        ];
        
        // Test each case
        foreach ($testCases as $input => $expected) {
            $track = Track::create([
                'title' => "Track with {$input}",
                'audio_url' => 'https://example.com/audio.mp3',
                'image_url' => 'https://example.com/image.jpg',
                'unique_id' => md5($input . time())
            ]);
            
            // Assign genres
            $track->assignGenres($input);
            Log::info("Created track with genre input: {$input}");
            
            // Check results
            $genres = $track->genres()->pluck('name')->toArray();
            
            if (is_array($expected)) {
                foreach ($expected as $exp) {
                    $this->assertContains($exp, $genres, "Should contain genre '{$exp}'");
                }
                $this->assertEquals(count($expected), count($genres), "Should have exactly " . count($expected) . " genres");
            } else {
                $this->assertContains($expected, $genres, "Should contain genre '{$expected}'");
                $this->assertEquals(1, count($genres), "Should have exactly 1 genre");
            }
        }
        
        Log::info('Completed test_genre_capitalization');
    }
} 