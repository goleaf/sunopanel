<?php

namespace Tests\Unit;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_track_factory()
    {
        $track = Track::factory()->create();

        $this->assertInstanceOf(Track::class, $track);
        $this->assertNotNull($track->title);
        $this->assertNotNull($track->unique_id);
        $this->assertDatabaseHas('tracks', [
            'id' => $track->id,
            'title' => $track->title,
        ]);
    }

    public function test_genre_factory()
    {
        $genre = Genre::factory()->create();

        $this->assertInstanceOf(Genre::class, $genre);
        $this->assertNotNull($genre->name);
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => $genre->name,
        ]);
    }

    public function test_playlist_factory()
    {
        $playlist = Playlist::factory()->create();

        $this->assertInstanceOf(Playlist::class, $playlist);
        $this->assertNotNull($playlist->title);
        $this->assertNotNull($playlist->description);
        $this->assertDatabaseHas('playlists', [
            'id' => $playlist->id,
            'title' => $playlist->title,
            'description' => $playlist->description,
        ]);
    }

    public function test_file_download_factory()
    {
        $this->markTestSkipped('File download functionality has been removed and merged into tracks');
    }

    public function test_track_factory_with_genre_relationship()
    {
        $track = Track::factory()->withGenres(2)->create();

        $this->assertInstanceOf(Track::class, $track);
        $this->assertEquals(2, $track->genres()->count());
        $track->load('genres');

        foreach ($track->genres as $genre) {
            $this->assertInstanceOf(Genre::class, $genre);
            $this->assertDatabaseHas('genre_track', [
                'track_id' => $track->id,
                'genre_id' => $genre->id,
            ]);
        }
    }

    public function test_playlist_factory_with_track_relationship()
    {
        $tracks = Track::factory()->count(3)->create();
        $playlist = Playlist::factory()->create();
        foreach ($tracks as $index => $track) {
            $playlist->addTrack($track, $index + 1);
        }

        $this->assertInstanceOf(Playlist::class, $playlist);
        $this->assertEquals(3, $playlist->tracks()->count());
        $playlistTracks = $playlist->tracks()->orderBy('position')->get();
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals($i + 1, $playlistTracks[$i]->pivot->position);
        }
    }

    public function test_file_download_related_files()
    {
        $this->markTestSkipped('File download functionality has been removed and merged into tracks');
    }

    public function test_playlist_factory_creates_valid_playlist()
    {
        $playlist = Playlist::factory()->create();
        $this->assertNotNull($playlist->id);
        $this->assertNotNull($playlist->title);
        $this->assertNotNull($playlist->created_at);
        $this->assertDatabaseHas('playlists', [
            'id' => $playlist->id,
            'title' => $playlist->title,
        ]);
    }
}
