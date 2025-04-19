<?php

namespace Tests\Unit;

use App\Models\Genre;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreCapitalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_genre_format_name_method(): void
    {
        $this->assertEquals('Bubblegum bass', Genre::formatGenreName('bubblegum bass'));
        $this->assertEquals('Bubblegum bass', Genre::formatGenreName('BUBBLEGUM BASS'));
        $this->assertEquals('Bubblegum bass', Genre::formatGenreName('bubblegum-bass'));
        $this->assertEquals('Drum and bass', Genre::formatGenreName('drum and bass'));
        $this->assertEquals('EDM', Genre::formatGenreName('edm'));
        $this->assertEquals('UK Garage', Genre::formatGenreName('uk garage'));
        $this->assertEquals('R&B', Genre::formatGenreName('r&b'));
        $this->assertEquals('Rock', Genre::formatGenreName('rock'));
        $this->assertEquals('Pop', Genre::formatGenreName('POP'));
        $this->assertEquals('Jazz', Genre::formatGenreName('jazz'));
        $this->assertEquals('Alternative Rock', Genre::formatGenreName('alternative rock'));
        $this->assertEquals('Jazz Fusion', Genre::formatGenreName('jazz fusion'));
        $this->assertEquals('Progressive Metal', Genre::formatGenreName('progressive metal'));
        $this->assertEquals('Symphony of the Night', Genre::formatGenreName('symphony of the night'));
        $this->assertEquals('Back in Time', Genre::formatGenreName('back in time'));
    }

    public function test_find_or_create_by_name(): void
    {
        $genre1 = Genre::findOrCreateByName('electronic dance music');
        $this->assertEquals('Electronic Dance Music', $genre1->name);
        $genre2 = Genre::findOrCreateByName('ELECTRONIC DANCE MUSIC');
        $this->assertEquals($genre1->id, $genre2->id);
        $this->assertEquals('Electronic Dance Music', $genre2->name);
        $genre3 = Genre::findOrCreateByName('bubblegum bass');
        $this->assertEquals('Bubblegum bass', $genre3->name);
        $genre4 = Genre::findOrCreateByName('BUBBLEGUM-BASS');
        $this->assertEquals($genre3->id, $genre4->id);
        $this->assertEquals('Bubblegum bass', $genre4->name);
    }

    public function test_track_sync_genres(): void
    {
        $track = Track::factory()->create();
        $track->syncGenres('electronic, rock, bubblegum bass');
        $genreNames = $track->genres->pluck('name')->toArray();

        $this->assertContains('Electronic', $genreNames);
        $this->assertContains('Rock', $genreNames);
        $this->assertContains('Bubblegum bass', $genreNames);
        $this->assertCount(3, $genreNames);
        $track->syncGenres('ELECTRONIC, Rock, bubblegum-bass, HIP HOP');
        $track->refresh();
        $genreNames = $track->genres->pluck('name')->toArray();

        $this->assertContains('Electronic', $genreNames);
        $this->assertContains('Rock', $genreNames);
        $this->assertContains('Bubblegum bass', $genreNames);
        $this->assertContains('Hip Hop', $genreNames);
        $this->assertCount(4, $genreNames);
        $this->assertEquals(1, Genre::where('name', 'Electronic')->count());
        $this->assertEquals(1, Genre::where('name', 'Rock')->count());
        $this->assertEquals(1, Genre::where('name', 'Bubblegum bass')->count());
        $this->assertEquals(1, Genre::where('name', 'Hip Hop')->count());
    }

    public function test_track_assign_genres(): void
    {
        $track = Track::factory()->create();
        $track->assignGenres('jazz, classical, r&b');
        $genreNames = $track->genres->pluck('name')->toArray();

        $this->assertContains('Jazz', $genreNames);
        $this->assertContains('Classical', $genreNames);
        $this->assertContains('R&B', $genreNames);
        $this->assertCount(3, $genreNames);
        $track->assignGenres('JAZZ, uk garage, pop');
        $track->refresh();
        $genreNames = $track->genres->pluck('name')->toArray();

        $this->assertContains('Jazz', $genreNames);
        $this->assertContains('UK Garage', $genreNames);
        $this->assertContains('Pop', $genreNames);
        $this->assertCount(3, $genreNames);
        $this->assertEquals(1, Genre::where('name', 'Jazz')->count());
        $this->assertEquals(1, Genre::where('name', 'Classical')->count());
        $this->assertEquals(1, Genre::where('name', 'R&B')->count());
        $this->assertEquals(1, Genre::where('name', 'UK Garage')->count());
        $this->assertEquals(1, Genre::where('name', 'Pop')->count());
    }
}
