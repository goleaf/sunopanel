<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Genre;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Services\Genre\GenreService;
use App\Models\Genre;
use App\Models\Track;
use App\Http\Requests\GenreStoreRequest;
use App\Http\Requests\GenreUpdateRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;

class GenreServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private GenreService $genreService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->genreService = new GenreService();
    }
    
    #[Test]
    public function testStore(): void
    {
        // Arrange
        $requestData = [
            'name' => 'Test Genre',
            'description' => 'Test Description'
        ];
        
        $storeRequest = $this->createMock(GenreStoreRequest::class);
        $storeRequest->method('validated')->willReturn($requestData);
        
        // Act
        $genre = $this->genreService->store($storeRequest);
        
        // Assert
        $this->assertInstanceOf(Genre::class, $genre);
        $this->assertEquals('Test Genre', $genre->name);
        $this->assertEquals('test-genre', $genre->slug);
        $this->assertEquals('Test Description', $genre->description);
    }
    
    #[Test]
    public function testUpdate(): void
    {
        // Arrange
        $genre = Genre::factory()->create([
            'name' => 'Original Genre',
            'slug' => 'original-genre',
            'description' => 'Original Description'
        ]);
        
        $requestData = [
            'name' => 'Updated Genre',
            'description' => 'Updated Description'
        ];
        
        $updateRequest = $this->createMock(GenreUpdateRequest::class);
        $updateRequest->method('validated')->willReturn($requestData);
        
        // Act
        $updatedGenre = $this->genreService->update($updateRequest, $genre);
        
        // Assert
        $this->assertEquals('Updated Genre', $updatedGenre->name);
        $this->assertEquals('updated-genre', $updatedGenre->slug); // Slug should be updated based on name
        $this->assertEquals('Updated Description', $updatedGenre->description);
    }
    
    #[Test]
    public function testDelete(): void
    {
        // Arrange
        $genre = Genre::factory()->create();
        
        // Act
        $result = $this->genreService->delete($genre);
        
        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }
    
    #[Test]
    public function testDeleteWithAssociatedTracks(): void
    {
        // Arrange
        $genre = Genre::factory()->create();
        $track = Track::factory()->create();
        $track->genres()->attach($genre);
        
        // Act
        $result = $this->genreService->delete($genre);
        
        // Assert
        $this->assertFalse($result);
        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }
    
    #[Test]
    public function testGetAll(): void
    {
        // Arrange
        Genre::factory()->count(5)->create();
        
        // Act
        $result = $this->genreService->getAll(2); // 2 per page
        
        // Assert
        $this->assertEquals(5, $result->total());
        $this->assertEquals(2, $result->count());
        $this->assertEquals(3, $result->lastPage());
    }
    
    #[Test]
    public function testGetWithTrackCounts(): void
    {
        // Arrange
        $genre1 = Genre::factory()->create(['name' => 'Genre A']);
        $genre2 = Genre::factory()->create(['name' => 'Genre B']);
        
        // Create tracks and associate with genres
        $track1 = Track::factory()->create();
        $track2 = Track::factory()->create();
        $track1->genres()->attach($genre1);
        $track2->genres()->attach($genre1);
        $track2->genres()->attach($genre2);
        
        // Act
        $result = $this->genreService->getWithTrackCounts();
        
        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        
        // Find genre A in results and check its track count
        $genreA = collect($result)->firstWhere('name', 'Genre A');
        $this->assertEquals(2, $genreA['tracks_count']);
        
        // Find genre B in results and check its track count
        $genreB = collect($result)->firstWhere('name', 'Genre B');
        $this->assertEquals(1, $genreB['tracks_count']);
    }
    
    #[Test]
    public function testGetWithTracks(): void
    {
        // Arrange
        $genre = Genre::factory()->create();
        Track::factory()->count(3)->create()->each(function ($track) use ($genre) {
            $track->genres()->attach($genre);
        });
        
        // Act
        $result = $this->genreService->getWithTracks($genre);
        
        // Assert
        $this->assertInstanceOf(Genre::class, $result);
        $this->assertTrue($result->relationLoaded('tracks'));
        $this->assertCount(3, $result->tracks);
    }
    
    #[Test]
    public function testFindBySlug(): void
    {
        // Arrange
        Genre::factory()->create(['name' => 'Test Genre', 'slug' => 'test-genre']);
        
        // Act
        $result = $this->genreService->findBySlug('test-genre');
        
        // Assert
        $this->assertInstanceOf(Genre::class, $result);
        $this->assertEquals('Test Genre', $result->name);
    }
    
    #[Test]
    public function testFindBySlugNotFound(): void
    {
        // Act
        $result = $this->genreService->findBySlug('non-existent-slug');
        
        // Assert
        $this->assertNull($result);
    }
}
