<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
public function tracks_table_has_required_columns()
    {
        $this->assertTrue(Schema::hasTable('tracks'));
        $this->assertTrue(Schema::hasColumns('tracks', [
            'id', 'title', 'audio_url', 'image_url', 'unique_id', 'duration',
            'created_at', 'updated_at',
        ]));
    }

    #[Test]
public function genres_table_has_required_columns()
    {
        $this->assertTrue(Schema::hasTable('genres'));
        $this->assertTrue(Schema::hasColumns('genres', [
            'id', 'name', 'slug', 'description', 'created_at', 'updated_at',
        ]));
    }

    #[Test]
public function playlists_table_has_required_columns()
    {
        $this->assertTrue(Schema::hasTable('playlists'));
        $this->assertTrue(Schema::hasColumns('playlists', [
            'id', 'title', 'description', 'cover_image', 'genre_id',
            'created_at', 'updated_at',
        ]));
    }

    #[Test]
public function genre_track_pivot_table_exists()
    {
        $this->assertTrue(Schema::hasTable('genre_track'));
        $this->assertTrue(Schema::hasColumns('genre_track', [
            'id', 'genre_id', 'track_id', 'created_at', 'updated_at',
        ]));
    }

    #[Test]
public function playlist_track_pivot_table_exists()
    {
        $this->assertTrue(Schema::hasTable('playlist_track'));
        $this->assertTrue(Schema::hasColumns('playlist_track', [
            'id', 'playlist_id', 'track_id', 'position', 'created_at', 'updated_at',
        ]));
    }

    #[Test]
public function track_belongs_to_many_genres()
    {
        $track = Track::factory()->create();
        $genre = Genre::factory()->create();

        $track->genres()->attach($genre);

        $this->assertTrue($track->genres->contains($genre));
        $this->assertTrue($genre->tracks->contains($track));
    }

    #[Test]
public function track_belongs_to_many_playlists()
    {
        $track = Track::factory()->create();
        $playlist = Playlist::factory()->create();

        $playlist->tracks()->attach($track);

        $this->assertTrue($track->playlists->contains($playlist));
        $this->assertTrue($playlist->tracks->contains($track));
    }

    #[Test]
public function playlist_belongs_to_genre()
    {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create(['genre_id' => $genre->id]);

        $this->assertEquals($genre->id, $playlist->genre_id);
        $this->assertEquals($genre->id, $playlist->genre->id);
    }

    #[Test]
public function deleting_track_removes_pivot_relationships()
    {
        $track = Track::factory()->create();
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create();

        $track->genres()->attach($genre);
        $track->playlists()->attach($playlist);

        $this->assertDatabaseHas('genre_track', [
            'genre_id' => $genre->id,
            'track_id' => $track->id,
        ]);

        $this->assertDatabaseHas('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track->id,
        ]);

        $track->delete();

        $this->assertDatabaseMissing('genre_track', [
            'genre_id' => $genre->id,
            'track_id' => $track->id,
        ]);

        $this->assertDatabaseMissing('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track->id,
        ]);
    }

    #[Test]
public function deleting_genre_removes_pivot_relationships()
    {
        $track = Track::factory()->create();
        $genre = Genre::factory()->create();

        $track->genres()->attach($genre);

        $this->assertDatabaseHas('genre_track', [
            'genre_id' => $genre->id,
            'track_id' => $track->id,
        ]);

        $genre->delete();

        $this->assertDatabaseMissing('genre_track', [
            'genre_id' => $genre->id,
            'track_id' => $track->id,
        ]);
    }

    #[Test]
public function deleting_playlist_removes_pivot_relationships()
    {
        $track = Track::factory()->create();
        $playlist = Playlist::factory()->create();

        $playlist->tracks()->attach($track);

        $this->assertDatabaseHas('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track->id,
        ]);

        $playlist->delete();

        $this->assertDatabaseMissing('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track->id,
        ]);
    }
}
