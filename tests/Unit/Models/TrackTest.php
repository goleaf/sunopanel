<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\Track;
use App\Models\Genre;
use App\Models\Playlist;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TrackTest extends TestCase
{
    use RefreshDatabase;
    
    #[Test]
    public function test_GetNameAttribute(): void
    {
        $track = Track::factory()->create(['title' => 'Test Track']);
        $name = $track->name;
        $this->assertEquals('Test Track', $name);
    }

    #[Test]
    public function test_SetNameAttribute(): void
    {
        $track = new Track();
        $track->name = 'Test Name';
        $this->assertEquals('Test Name', $track->title);
    }

    #[Test]
    public function test_Genres(): void
    {
        $track = Track::factory()->create();
        $genre = Genre::factory()->create();
        $track->genres()->attach($genre);
        $this->assertCount(1, $track->genres);
        $this->assertEquals($genre->id, $track->genres->first()->id);
    }

    #[Test]
    public function test_Playlists(): void
    {
        $track = Track::factory()->create();
        $playlist = Playlist::factory()->create();
        $track->playlists()->attach($playlist, ['position' => 1]);
        $this->assertCount(1, $track->playlists);
        $this->assertEquals($playlist->id, $track->playlists->first()->id);
        $this->assertEquals(1, $track->playlists->first()->pivot->position);
    }

    #[Test]
    public function test_SyncGenres(): void
    {
        $track = Track::factory()->create();
        $genreString = 'Rock, Pop, Jazz';
        $track->syncGenres($genreString);
        $this->assertCount(3, $track->genres);
        $genreNames = $track->genres->pluck('name')->toArray();
        $this->assertContains('Rock', $genreNames);
        $this->assertContains('Pop', $genreNames);
        $this->assertContains('Jazz', $genreNames);
    }

    #[Test]
    public function test_AssignGenres(): void
    {
        $track = Track::factory()->create();
        $genreString = 'Electronic, Ambient';
        $track->assignGenres($genreString);
        $this->assertCount(2, $track->genres);
        $genreNames = $track->genres->pluck('name')->toArray();
        $this->assertContains('Electronic', $genreNames);
        $this->assertContains('Ambient', $genreNames);
    }

    #[Test]
    public function test_GetGenresListAttribute(): void
    {
        $track = Track::factory()->create();
        Genre::factory()->create(['name' => 'Metal']);
        Genre::factory()->create(['name' => 'Rock']);
        $track->genres()->attach(Genre::all());
        $genresList = $track->genres_list;
        $this->assertStringContainsString('Metal', $genresList);
        $this->assertStringContainsString('Rock', $genresList);
        $this->assertStringContainsString(', ', $genresList);
    }

    #[Test]
    public function test_GetGenresArray(): void
    {
        $track = Track::factory()->create();
        Genre::factory()->create(['name' => 'Blues']);
        Genre::factory()->create(['name' => 'Jazz']);
        $track->genres()->attach(Genre::all());
        $genres = $track->getGenresArray();
        $this->assertIsArray($genres);
        $this->assertContains('Blues', $genres);
        $this->assertContains('Jazz', $genres);
    }

    #[Test]
    public function test_GetGenresString(): void
    {
        $track = Track::factory()->create();
        Genre::factory()->create(['name' => 'Classical']);
        Genre::factory()->create(['name' => 'Opera']);
        $track->genres()->attach(Genre::all());
        $genresString = $track->getGenresString();
        $this->assertStringContainsString('Classical', $genresString);
        $this->assertStringContainsString('Opera', $genresString);
    }

    #[Test]
    public function test_FormatGenres(): void
    {
        $genresString = 'rap, rock, pop';
        $formatted = Track::formatGenres($genresString);
        $this->assertEquals('Rap, Rock, Pop', $formatted);
    }

    #[Test]
    public function test_GenerateUniqueId(): void
    {
        $title = 'Test Track Title';
        $uniqueId = Track::generateUniqueId($title);
        $this->assertEquals('test-track-title', $uniqueId);
        Track::factory()->create(['unique_id' => 'test-track-title']);
        $secondId = Track::generateUniqueId($title);
        $this->assertEquals('test-track-title-1', $secondId);
    }

    #[Test]
    public function test_GetDurationSecondsAttribute(): void
    {
        $track = Track::factory()->create(['duration' => '3:45']);
        $seconds = $track->duration_seconds;
        $this->assertEquals(225, $seconds);
    }

    #[Test]
    public function test_GetStoreFields(): void
    {
        $fields = (new Track())->getStoreFields();
        $this->assertIsArray($fields);
        $this->assertContains('title', $fields);
        $this->assertContains('audio_url', $fields);
        $this->assertContains('image_url', $fields);
        $this->assertContains('duration', $fields);
    }

    #[Test]
    public function test_GetUpdateFields(): void
    {
        $fields = (new Track())->getUpdateFields();
        $this->assertIsArray($fields);
        $this->assertContains('title', $fields);
        $this->assertContains('audio_url', $fields);
        $this->assertContains('image_url', $fields);
    }

    #[Test]
    public function test_GetDeleteFields(): void
    {
        $fields = (new Track())->getDeleteFields();
        $this->assertIsArray($fields);
        $this->assertContains('id', $fields);
    }

    #[Test]
    public function test_Factory(): void
    {
        $track = Track::factory()->create();
        $this->assertInstanceOf(Track::class, $track);
        $this->assertNotEmpty($track->title);
        $this->assertNotEmpty($track->audio_url);
        $this->assertNotEmpty($track->image_url);
    }
}
