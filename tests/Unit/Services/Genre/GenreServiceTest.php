<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Genre;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Services\Genre\GenreService;
use App\Models\Genre;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        $requestData = [
            'name' => 'Test Genre',
            'description' => 'Test Description'
        ];
        $genre = $this->genreService->storeFromArray($requestData);
        $this->assertInstanceOf(Genre::class, $genre);
        $this->assertEquals('Test Genre', $genre->name);
        $this->assertEquals('test-genre', $genre->slug);
        $this->assertEquals('Test Description', $genre->description);
    }
    
    #[Test]
    public function testUpdate(): void
    {
        $genre = Genre::factory()->create([
            'name' => 'Original Genre',
            'slug' => 'original-genre',
            'description' => 'Original Description'
        ]);
        
        $requestData = [
            'name' => 'Updated Genre',
            'description' => 'Updated Description'
        ];
        $updatedGenre = $this->genreService->updateFromArray($requestData, $genre);
        $this->assertEquals('Updated Genre', $updatedGenre->name);
        $this->assertEquals('updated-genre', $updatedGenre->slug);
        $this->assertEquals('Updated Description', $updatedGenre->description);
    }
    
    #[Test]
    public function testDelete(): void
    {
        $genre = Genre::factory()->create();
        $result = $this->genreService->delete($genre);
        $this->assertTrue($result);
        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }
    
    #[Test]
    public function testDeleteWithAssociatedTracks(): void
    {
        $genre = Genre::factory()->create();
        $track = Track::factory()->create();
        $track->genres()->attach($genre);
        $result = $this->genreService->delete($genre);
        $this->assertFalse($result);
        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }
    
    #[Test]
    public function testGetAll(): void
    {
        Genre::factory()->count(5)->create();
        $result = $this->genreService->getAll(2);

        $this->assertEquals(5, $result->total());
        $this->assertEquals(2, $result->count());
        $this->assertEquals(3, $result->lastPage());
    }
    
    #[Test]
    public function testGetWithTrackCounts(): void
    {
        $genreA = Genre::factory()->create(['name' => 'Genre A']);
        $genreB = Genre::factory()->create(['name' => 'Genre B']);
        $trackA = Track::factory()->create(['title' => 'Track A']);
        $trackB = Track::factory()->create(['title' => 'Track B']);
        $trackC = Track::factory()->create(['title' => 'Track C']);
        $trackA->genres()->attach($genreA);
        $trackB->genres()->attach($genreA);
        $trackC->genres()->attach($genreB);
        $results = $this->genreService->getWithTrackCounts();
        $this->assertNotEmpty($results);
        dump($results);
        $resultCollection = collect($results);
        $genreAResult = collect($results)->firstWhere('id', $genreA->id);
        $genreBResult = collect($results)->firstWhere('id', $genreB->id);
        
        $this->assertNotNull($genreAResult, 'Genre A not found by ID in results');
        $this->assertNotNull($genreBResult, 'Genre B not found by ID in results');
        $this->assertEquals(2, $genreAResult['tracks_count']);
        $this->assertEquals(1, $genreBResult['tracks_count']);
    }
    
    #[Test]
    public function testGetWithTracks(): void
    {
        $genre = Genre::factory()->create();
        Track::factory()->count(3)->create()->each(function ($track) use ($genre) {
            $track->genres()->attach($genre);
        });
        $result = $this->genreService->getWithTracks($genre);
        $this->assertInstanceOf(Genre::class, $result);
        $this->assertTrue($result->relationLoaded('tracks'));
        $this->assertCount(3, $result->tracks);
    }
    
    #[Test]
    public function testFindBySlug(): void
    {
        Genre::factory()->create(['name' => 'Test Genre', 'slug' => 'test-genre']);
        $result = $this->genreService->findBySlug('test-genre');
        $this->assertInstanceOf(Genre::class, $result);
        $this->assertEquals('Test Genre', $result->name);
    }
    
    #[Test]
    public function testFindBySlugNotFound(): void
    {
        $result = $this->genreService->findBySlug('non-existent-slug');
        $this->assertNull($result);
    }
}
