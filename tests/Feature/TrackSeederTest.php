<?php

namespace Tests\Feature;

use App\Models\Track;
use Database\Seeders\TrackSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_track_seeder_creates_tracks(): void
    {
        \DB::statement('PRAGMA foreign_keys = OFF');
        \DB::table('genre_track')->truncate();
        \DB::table('genres')->truncate();
        \DB::table('tracks')->truncate();
        \DB::statement('PRAGMA foreign_keys = ON');
        $this->seed(TrackSeeder::class);
        $this->assertDatabaseCount('tracks', 20);
        $firstTrack = Track::first();
        $this->assertNotNull($firstTrack->title);
        $this->assertNotNull($firstTrack->audio_url);
        $this->assertNotNull($firstTrack->image_url);
        $this->assertNotEmpty($firstTrack->genres);
    }

    public function test_track_seeder_creates_properly_formatted_genres(): void
    {
        \DB::statement('PRAGMA foreign_keys = OFF');
        \DB::table('genre_track')->truncate();
        \DB::table('genres')->truncate();
        \DB::table('tracks')->truncate();
        \DB::statement('PRAGMA foreign_keys = ON');
        $this->seed(TrackSeeder::class);
        $this->assertDatabaseHas('genres', [
            'name' => 'Bubblegum bass',
        ]);
        $this->assertDatabaseMissing('genres', [
            'name' => 'bubblegum bass',
        ]);
        $this->assertDatabaseHas('genres', [
            'name' => 'Hypnotic trance',
        ]);
    }
}
