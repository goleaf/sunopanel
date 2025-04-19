<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Http\Controllers\TrackController;
use App\Http\Requests\BulkTrackRequest;
use App\Models\Track;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class TrackControllerTest extends TestCase
{
    use RefreshDatabase;
    
    #[Test]
    public function testIndex(): void
    {
        // Arrange
        Track::factory()->count(3)->create();
        
        // Act
        $response = $this->get(route('tracks.index'));
        
        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('tracks.index');
        $response->assertViewHas('tracks');
    }
    
    #[Test]
    public function testIndexWithSearch(): void
    {
        // Arrange
        Track::factory()->create(['title' => 'Test Track']);
        Track::factory()->create(['title' => 'Another Track']);
        
        // Act
        $response = $this->get(route('tracks.index', ['search' => 'Test']));
        
        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('tracks.index');
        $response->assertViewHas('tracks');
        $tracks = $response->viewData('tracks');
        $this->assertEquals(1, $tracks->count());
        $this->assertEquals('Test Track', $tracks->first()->title);
    }

    #[Test]
    public function testCreate(): void
    {
        // Arrange
        Genre::factory()->count(3)->create();
        
        // Act
        $response = $this->get(route('tracks.create'));
        
        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('tracks.create');
        $response->assertViewHas('genres');
    }

    #[Test]
    public function testStore(): void
    {
        // Arrange
        $genre = Genre::factory()->create();
        $trackData = [
            'title' => 'New Test Track',
            'audio_url' => 'https://example.com/audio.mp3',
            'image_url' => 'https://example.com/image.jpg',
            'duration' => '3:45',
            'genres' => $genre->name
        ];
        
        // Act
        $response = $this->post(route('tracks.store'), $trackData);
        
        // Assert
        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tracks', [
            'title' => 'New Test Track',
            'audio_url' => 'https://example.com/audio.mp3'
        ]);
        
        // Check that the genre was attached
        $track = Track::where('title', 'New Test Track')->first();
        $this->assertCount(1, $track->genres);
        $this->assertEquals($genre->id, $track->genres->first()->id);
    }

    #[Test]
    public function testProcessBulkUpload(): void
    {
        // Arrange
        // Register a separate bulk upload route for testing
        Route::post('tracks/bulk-upload', [TrackController::class, 'processBulkUpload'])
            ->middleware('web')
            ->name('tracks.bulk-upload');
            
        $bulkData = [
            'bulk_tracks' => "Test Track 1|https://example.com/audio1.mp3|https://example.com/image1.jpg|Rock\n" .
                           "Test Track 2|https://example.com/audio2.mp3|https://example.com/image2.jpg|Pop, Jazz"
        ];
        
        // Act
        $request = BulkTrackRequest::create('tracks/bulk-upload', 'POST', $bulkData);
        $request->setContainer(app())->setRedirector(app()->make('redirect'));
        
        $controller = app()->make(TrackController::class);
        $response = $controller->processBulkUpload($request);
        
        // Assert
        $this->assertTrue($response->isRedirect(route('tracks.index')));
        $this->assertDatabaseHas('tracks', ['title' => 'Test Track 1']);
        $this->assertDatabaseHas('tracks', ['title' => 'Test Track 2']);
        
        // Check genres
        $track1 = Track::where('title', 'Test Track 1')->first();
        $this->assertCount(1, $track1->genres);
        $this->assertEquals('Rock', $track1->genres->first()->name);
        
        $track2 = Track::where('title', 'Test Track 2')->first();
        $this->assertCount(2, $track2->genres);
    }

    #[Test]
    public function testShow(): void
    {
        // Arrange
        $track = Track::factory()->create();
        
        // Act
        $response = $this->get(route('tracks.show', $track->id));
        
        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('tracks.show');
        $response->assertViewHas('track');
        $this->assertEquals($track->id, $response->viewData('track')->id);
    }

    #[Test]
    public function testEdit(): void
    {
        // Arrange
        $track = Track::factory()->create();
        
        // Act
        $response = $this->get(route('tracks.edit', $track->id));
        
        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('tracks.edit');
        $response->assertViewHas('track');
        $response->assertViewHas('genres');
    }

    #[Test]
    public function testUpdate(): void
    {
        // Arrange
        $track = Track::factory()->create(['title' => 'Original Title']);
        $genre = Genre::factory()->create();
        
        $updateData = [
            'title' => 'Updated Title',
            'audio_url' => 'https://example.com/updated.mp3',
            'image_url' => 'https://example.com/updated.jpg',
            'duration' => '4:30',
            'genres' => $genre->name
        ];
        
        // Act
        $response = $this->put(route('tracks.update', $track->id), $updateData);
        
        // Assert
        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tracks', [
            'id' => $track->id,
            'title' => 'Updated Title',
            'audio_url' => 'https://example.com/updated.mp3'
        ]);
        
        // Verify genre was updated
        $track->refresh();
        $this->assertCount(1, $track->genres);
        $this->assertEquals($genre->id, $track->genres->first()->id);
    }

    #[Test]
    public function testDestroy(): void
    {
        // Arrange
        $track = Track::factory()->create();
        
        // Act
        $response = $this->delete(route('tracks.destroy', $track->id));
        
        // Assert
        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('tracks', ['id' => $track->id]);
    }

    #[Test]
    public function testPlay(): void
    {
        // Arrange
        $track = Track::factory()->create(['audio_url' => 'https://example.com/audio.mp3']);
        
        // Act
        $response = $this->get(route('tracks.play', $track->id));
        
        // Assert
        $response->assertRedirect($track->audio_url);
    }
}
