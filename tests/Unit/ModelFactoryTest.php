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

    /**
     * Test that Track factory works correctly.
     *
     * @return void
     */
    public function test_track_factory()
    {
        $track = Track::factory()->create();
        
        $this->assertInstanceOf(Track::class, $track);
        $this->assertNotNull($track->title);
        $this->assertNotNull($track->unique_id);
        $this->assertDatabaseHas('tracks', [
            'id' => $track->id,
            'title' => $track->title
        ]);
    }

    /**
     * Test that Genre factory works correctly.
     *
     * @return void
     */
    public function test_genre_factory()
    {
        $genre = Genre::factory()->create();
        
        $this->assertInstanceOf(Genre::class, $genre);
        $this->assertNotNull($genre->name);
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => $genre->name
        ]);
    }

    /**
     * Test that Playlist factory works correctly.
     *
     * @return void
     */
    public function test_playlist_factory()
    {
        $playlist = Playlist::factory()->create();
        
        $this->assertInstanceOf(Playlist::class, $playlist);
        $this->assertNotNull($playlist->name);
        $this->assertNotNull($playlist->description);
        $this->assertDatabaseHas('playlists', [
            'id' => $playlist->id,
            'name' => $playlist->name,
            'description' => $playlist->description
        ]);
    }

    /**
     * Test that FileDownload factory works correctly.
     *
     * @return void
     */
    public function test_file_download_factory()
    {
        $this->markTestSkipped('File download functionality has been removed and merged into tracks');
    }

    /**
     * Test that Track factory with a relationship to Genre works.
     *
     * @return void
     */
    public function test_track_factory_with_genre_relationship()
    {
        // Create a track with 2 genres using our custom withGenres method
        $track = Track::factory()->withGenres(2)->create();
        
        $this->assertInstanceOf(Track::class, $track);
        $this->assertEquals(2, $track->genres()->count());
        
        // Load the relationships
        $track->load('genres');
        
        foreach ($track->genres as $genre) {
            $this->assertInstanceOf(Genre::class, $genre);
            $this->assertDatabaseHas('genre_track', [
                'track_id' => $track->id,
                'genre_id' => $genre->id
            ]);
        }
    }

    /**
     * Test that Playlist factory with Track relationships works.
     *
     * @return void
     */
    public function test_playlist_factory_with_track_relationship()
    {
        // Create tracks first
        $tracks = Track::factory()->count(3)->create();
        
        // Create a playlist
        $playlist = Playlist::factory()->create();
        
        // Attach tracks with positions
        foreach ($tracks as $index => $track) {
            $playlist->addTrack($track, $index + 1);
        }
        
        $this->assertInstanceOf(Playlist::class, $playlist);
        $this->assertEquals(3, $playlist->tracks()->count());
        
        // Check that positions are set correctly
        $playlistTracks = $playlist->tracks()->orderBy('position')->get();
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals($i + 1, $playlistTracks[$i]->pivot->position);
        }
    }

    /**
     * Test that FileDownload can have related files.
     *
     * @return void
     */
    public function test_file_download_related_files()
    {
        $this->markTestSkipped('File download functionality has been removed and merged into tracks');
    }
} 