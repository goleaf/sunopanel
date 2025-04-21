<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
    }

    public static function provide_relationship_types(): array
    {
        return [
            'tracks relationship' => [
                'relationMethod' => 'tracks',
                'relatedModel' => Track::class,
                'relationType' => 'BelongsToMany'
            ],
            'playlists relationship' => [
                'relationMethod' => 'playlists',
                'relatedModel' => Playlist::class,
                'relationType' => 'HasMany'
            ],
        ];
    }

    #[Test]
    #[DataProvider('provide_relationship_types')]
    public function test_relationship_returns_expected_relation_type(string $relationMethod, string $relatedModel, string $relationType): void
    {
        $genre = new Genre();
        $relation = $genre->{$relationMethod}();
        
        $this->assertNotNull($relation);
        $this->assertStringContainsString($relationType, get_class($relation));
        $this->assertEquals($relatedModel, get_class($relation->getRelated()));
    }

    public static function provide_genre_name_formats(): array
    {
        return [
            'lowercase name' => [
                'input' => 'pop rock',
                'expected' => 'Pop Rock'
            ],
            'uppercase name' => [
                'input' => 'POP ROCK',
                'expected' => 'Pop Rock'
            ],
            'mixed case name' => [
                'input' => 'PoP rOcK',
                'expected' => 'Pop Rock'
            ],
            'name with extra spaces' => [
                'input' => '  jazz fusion  ',
                'expected' => 'Jazz Fusion'
            ],
        ];
    }

    #[Test]
    #[DataProvider('provide_genre_name_formats')]
    public function test_set_name_attribute_properly_formats_genre_name(string $input, string $expected): void
    {
        $genre = new Genre();
        $genre->name = $input;
        
        $this->assertEquals($expected, $genre->name);
    }

    #[Test]
    #[DataProvider('provide_genre_name_formats')]
    public function test_format_genre_name_properly_formats_input_string(string $input, string $expected): void
    {
        $this->assertEquals($expected, Genre::formatGenreName($input));
    }

    #[Test]
    public function test_FindOrCreateByName(): void
    {
        // Reset the db to ensure we're starting fresh
        $this->refreshDatabase();
        
        $genreName = 'Electronic Dance';
        $expectedFormattedName = 'Electronic Dance';
        
        // First test: Creating a new genre
        $genre = Genre::findOrCreateByName($genreName);
        
        $this->assertNotNull($genre);
        $this->assertInstanceOf(Genre::class, $genre);
        $this->assertEquals($expectedFormattedName, $genre->name);
        $this->assertDatabaseHas('genres', ['name' => $expectedFormattedName]);
        
        // Second test: Finding an existing genre
        $sameGenre = Genre::findOrCreateByName($genreName);
        
        $this->assertEquals($genre->id, $sameGenre->id);
        $this->assertEquals(1, Genre::where('name', $expectedFormattedName)->count());
    }

    #[Test]
    public function test_Factory(): void
    {
        $genre = Genre::factory()->create();
        
        $this->assertNotNull($genre);
        $this->assertInstanceOf(Genre::class, $genre);
        $this->assertNotNull($genre->id);
        $this->assertNotEmpty($genre->name);
    }
}
