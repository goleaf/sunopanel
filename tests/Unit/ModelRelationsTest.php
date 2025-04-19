<?php

namespace Tests\Unit;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_track_genre_relationship(): void
    {
        $track = Track::factory()->create();
        $genre = Genre::factory()->create();

        $track->genres()->attach($genre);
        $track = $track->fresh(['genres']);
        $genre = $genre->fresh(['tracks']);

        $this->assertTrue($track->genres->contains($genre->id));
        $this->assertTrue($genre->tracks->contains($track->id));
    }

    public function test_playlist_track_relationship(): void
    {
        $playlist = Playlist::factory()->create();
        $track = Track::factory()->create();

        $playlist->tracks()->attach($track, ['position' => 1]);

        $this->assertTrue($playlist->tracks->contains($track));
        $this->assertDatabaseHas('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track->id,
            'position' => 1,
        ]);
    }

    public function test_playlist_genre_relationship(): void
    {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create(['genre_id' => $genre->id]);

        $this->assertEquals($genre->id, $playlist->genre->id);
        $this->assertTrue($genre->playlists->contains($playlist));
    }

    public function test_track_methods(): void
    {
        $track = Track::factory()->create([
            'name' => 'Test Track',
        ]);
        $uniqueId = Track::generateUniqueId('Test Track');
        $this->assertNotEmpty($uniqueId);
        $rock = Genre::factory()->create(['name' => 'Rock']);
        $pop = Genre::factory()->create(['name' => 'Pop']);
        $jazz = Genre::factory()->create(['name' => 'Jazz']);
        $track->genres()->attach([$rock->id, $pop->id, $jazz->id]);

        $this->assertEquals(3, $track->genres()->count());
        $formattedGenres = Track::formatGenres('rock, pop, jazz');
        $this->assertEquals('Rock, Pop, Jazz', $formattedGenres);
    }
}
