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
    public function testGetNameAttribute(): void
    {
        // Arrange
        $track = Track::factory()->create(['title' => 'Test Track']);
        
        // Act
        $name = $track->name;
        
        // Assert
        $this->assertEquals('Test Track', $name);
    }

    #[Test]
    public function testSetNameAttribute(): void
    {
        // Arrange
        $track = new Track();
        
        // Act
        $track->name = 'Test Name';
        
        // Assert
        $this->assertEquals('Test Name', $track->title);
    }

    #[Test]
    public function testGenres(): void
    {
        // Arrange
        $track = Track::factory()->create();
        $genre = Genre::factory()->create();
        
        // Act
        $track->genres()->attach($genre);
        
        // Assert
        $this->assertCount(1, $track->genres);
        $this->assertEquals($genre->id, $track->genres->first()->id);
    }

    #[Test]
    public function testPlaylists(): void
    {
        // Arrange
        $track = Track::factory()->create();
        $playlist = Playlist::factory()->create();
        
        // Act
        $track->playlists()->attach($playlist, ['position' => 1]);
        
        // Assert
        $this->assertCount(1, $track->playlists);
        $this->assertEquals($playlist->id, $track->playlists->first()->id);
        $this->assertEquals(1, $track->playlists->first()->pivot->position);
    }

    #[Test]
    public function testSyncGenres(): void
    {
        // Arrange
        $track = Track::factory()->create();
        $genreString = 'Rock, Pop, Jazz';
        
        // Act
        $track->syncGenres($genreString);
        
        // Assert
        $this->assertCount(3, $track->genres);
        $genreNames = $track->genres->pluck('name')->toArray();
        $this->assertContains('Rock', $genreNames);
        $this->assertContains('Pop', $genreNames);
        $this->assertContains('Jazz', $genreNames);
    }

    #[Test]
    public function testAssignGenres(): void
    {
        // Arrange
        $track = Track::factory()->create();
        $genreString = 'Electronic, Ambient';
        
        // Act
        $track->assignGenres($genreString);
        
        // Assert
        $this->assertCount(2, $track->genres);
        $genreNames = $track->genres->pluck('name')->toArray();
        $this->assertContains('Electronic', $genreNames);
        $this->assertContains('Ambient', $genreNames);
    }

    #[Test]
    public function testGetGenresListAttribute(): void
    {
        // Arrange
        $track = Track::factory()->create();
        Genre::factory()->create(['name' => 'Metal']);
        Genre::factory()->create(['name' => 'Rock']);
        $track->genres()->attach(Genre::all());
        
        // Act
        $genresList = $track->genres_list;
        
        // Assert
        $this->assertStringContainsString('Metal', $genresList);
        $this->assertStringContainsString('Rock', $genresList);
        $this->assertStringContainsString(', ', $genresList);
    }

    #[Test]
    public function testGetGenresArray(): void
    {
        // Arrange
        $track = Track::factory()->create();
        Genre::factory()->create(['name' => 'Blues']);
        Genre::factory()->create(['name' => 'Jazz']);
        $track->genres()->attach(Genre::all());
        
        // Act
        $genres = $track->getGenresArray();
        
        // Assert
        $this->assertIsArray($genres);
        $this->assertContains('Blues', $genres);
        $this->assertContains('Jazz', $genres);
    }

    #[Test]
    public function testGetGenresString(): void
    {
        // Arrange
        $track = Track::factory()->create();
        Genre::factory()->create(['name' => 'Classical']);
        Genre::factory()->create(['name' => 'Opera']);
        $track->genres()->attach(Genre::all());
        
        // Act
        $genresString = $track->getGenresString();
        
        // Assert
        $this->assertStringContainsString('Classical', $genresString);
        $this->assertStringContainsString('Opera', $genresString);
    }

    #[Test]
    public function testFormatGenres(): void
    {
        // Arrange
        $genresString = 'rap, rock, pop';
        
        // Act
        $formatted = Track::formatGenres($genresString);
        
        // Assert
        $this->assertEquals('Rap, Rock, Pop', $formatted);
    }

    #[Test]
    public function testGenerateUniqueId(): void
    {
        // Arrange
        $title = 'Test Track Title';
        
        // Act
        $uniqueId = Track::generateUniqueId($title);
        
        // Assert
        $this->assertEquals('test-track-title', $uniqueId);
        
        // Create a track with this ID to test the uniqueness logic
        Track::factory()->create(['unique_id' => 'test-track-title']);
        $secondId = Track::generateUniqueId($title);
        $this->assertEquals('test-track-title-1', $secondId);
    }

    #[Test]
    public function testGetDurationSecondsAttribute(): void
    {
        // Arrange
        $track = Track::factory()->create(['duration' => '3:45']);
        
        // Act
        $seconds = $track->duration_seconds;
        
        // Assert
        $this->assertEquals(225, $seconds);
    }

    #[Test]
    public function testGetStoreFields(): void
    {
        // Arrange & Act
        $fields = (new Track())->getStoreFields();
        
        // Assert
        $this->assertIsArray($fields);
        $this->assertContains('title', $fields);
        $this->assertContains('audio_url', $fields);
        $this->assertContains('image_url', $fields);
        $this->assertContains('duration', $fields);
    }

    #[Test]
    public function testGetUpdateFields(): void
    {
        // Arrange & Act
        $fields = (new Track())->getUpdateFields();
        
        // Assert
        $this->assertIsArray($fields);
        $this->assertContains('title', $fields);
        $this->assertContains('audio_url', $fields);
        $this->assertContains('image_url', $fields);
    }

    #[Test]
    public function testGetDeleteFields(): void
    {
        // Arrange & Act
        $fields = (new Track())->getDeleteFields();
        
        // Assert
        $this->assertIsArray($fields);
        $this->assertContains('id', $fields);
    }

    #[Test]
    public function testFactory(): void
    {
        // Arrange & Act
        $track = Track::factory()->create();
        
        // Assert
        $this->assertInstanceOf(Track::class, $track);
        $this->assertNotEmpty($track->title);
        $this->assertNotEmpty($track->audio_url);
        $this->assertNotEmpty($track->image_url);
    }
}
