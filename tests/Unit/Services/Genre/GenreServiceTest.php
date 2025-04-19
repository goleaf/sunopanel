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
        
        // Act
        $genre = $this->genreService->storeFromArray($requestData);
        
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
        
        // Act
        $updatedGenre = $this->genreService->updateFromArray($requestData, $genre);
        
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
        $genreA = Genre::factory()->create(['name' => 'Genre A']);
        $genreB = Genre::factory()->create(['name' => 'Genre B']);
        $trackA = Track::factory()->create(['title' => 'Track A']);
        $trackB = Track::factory()->create(['title' => 'Track B']);
        $trackC = Track::factory()->create(['title' => 'Track C']);
        $trackA->genres()->attach($genreA);
        $trackB->genres()->attach($genreA);
        $trackC->genres()->attach($genreB);
        
        // Act
        $results = $this->genreService->getWithTrackCounts();
        
        // Assert
        $this->assertNotEmpty($results);
        
        // Debug output
        dump($results);
        
        // Use collection to make it easier to find items in the array
        $resultCollection = collect($results);
        
        // Retrieve all genres by ID for easier debugging
        $genreAResult = collect($results)->firstWhere('id', $genreA->id);
        $genreBResult = collect($results)->firstWhere('id', $genreB->id);
        
        $this->assertNotNull($genreAResult, 'Genre A not found by ID in results');
        $this->assertNotNull($genreBResult, 'Genre B not found by ID in results');
        
        // Check track counts
        $this->assertEquals(2, $genreAResult['tracks_count']);
        $this->assertEquals(1, $genreBResult['tracks_count']);
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
