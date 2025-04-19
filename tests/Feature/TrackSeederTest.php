<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Track;
use App\Models\Genre;
use Database\Seeders\TrackSeeder;

class TrackSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the track seeder creates tracks correctly.
     */
    public function test_track_seeder_creates_tracks(): void
    {
        // Truncate related tables to ensure a clean state
        \DB::statement('PRAGMA foreign_keys = OFF');
        \DB::table('genre_track')->truncate();
        \DB::table('genres')->truncate();
        \DB::table('tracks')->truncate();
        \DB::statement('PRAGMA foreign_keys = ON');
        
        // Run the seeder
        $this->seed(TrackSeeder::class);
        
        // Check if tracks were created
        $this->assertDatabaseCount('tracks', 20);
        
        // Check that all required fields are populated
        $firstTrack = Track::first();
        $this->assertNotNull($firstTrack->title);
        $this->assertNotNull($firstTrack->audio_url);
        $this->assertNotNull($firstTrack->image_url);
        
        // Check for genres relationship
        $this->assertNotEmpty($firstTrack->genres);
    }
    
    /**
     * Test that genres are correctly formatted with first letter capitalized 
     * and special handling for "Bubblegum bass".
     */
    public function test_track_seeder_creates_properly_formatted_genres(): void
    {
        // Truncate related tables to ensure a clean state
        \DB::statement('PRAGMA foreign_keys = OFF');
        \DB::table('genre_track')->truncate();
        \DB::table('genres')->truncate();
        \DB::table('tracks')->truncate();
        \DB::statement('PRAGMA foreign_keys = ON');
        
        // Run the seeder
        $this->seed(TrackSeeder::class);
        
        // Check that genres are created and properly formatted
        $this->assertDatabaseHas('genres', [
            'name' => 'Bubblegum bass'
        ]);
        
        // Bubblegum bass should have first letter capitalized
        $this->assertDatabaseMissing('genres', [
            'name' => 'bubblegum bass'
        ]);
        
        // Check that other genres are properly capitalized
        $this->assertDatabaseHas('genres', [
            'name' => 'Hypnotic trance'
        ]);
    }
} 