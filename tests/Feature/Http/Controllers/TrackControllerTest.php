<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\Track;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TrackControllerTest extends TestCase
{
    use RefreshDatabase;
    
    #[Test]
    public function testIndex(): void
    {
        Track::factory()->count(3)->create();
        $response = $this->get(route('tracks.index'));
        $response->assertStatus(200);
        $response->assertViewIs('tracks.index');
        $response->assertViewHas('tracks');
    }
    
    #[Test]
    public function testIndexWithSearch(): void
    {
        Track::factory()->create(['title' => 'Test Track']);
        Track::factory()->create(['title' => 'Another Track']);
        $response = $this->get(route('tracks.index', ['search' => 'Test']));
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
        Genre::factory()->count(3)->create();
        $response = $this->get(route('tracks.create'));
        $response->assertStatus(200);
        $response->assertViewIs('tracks.create');
        $response->assertViewHas('genres');
    }

    #[Test]
    public function testStore(): void
    {
        $genre = Genre::factory()->create();
        $trackData = [
            'title' => 'New Test Track',
            'audio_url' => 'https:
            'image_url' => 'https:
            'duration' => '3:45',
            'genres' => $genre->name
        ];
        $response = $this->post(route('tracks.store'), $trackData);
        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tracks', [
            'title' => 'New Test Track',
            'audio_url' => 'https:
        ]);
        $track = Track::where('title', 'New Test Track')->first();
        $this->assertCount(1, $track->genres);
        $this->assertEquals($genre->id, $track->genres->first()->id);
    }

    #[Test]
    public function testProcessBulkUpload(): void
    {
        $bulkData = [
            'bulk_tracks' => "Test Track 1|https:
                          "Test Track 2|https:
        ];
        $response = $this->post(route('tracks.store'), $bulkData);
        $response->assertRedirect();
        $this->assertTrue(str_contains($response->headers->get('Location'), route('tracks.bulk-upload')));
        $bulkResponse = $this->post(route('tracks.bulk-upload'), $bulkData);
        $bulkResponse->assertRedirect(route('tracks.index'));
        $bulkResponse->assertSessionHas('success');
        $this->assertDatabaseHas('tracks', ['title' => 'Test Track 1']);
        $this->assertDatabaseHas('tracks', ['title' => 'Test Track 2']);
        $track1 = Track::where('title', 'Test Track 1')->first();
        $this->assertNotNull($track1, 'Track 1 was not found in the database');
        $this->assertCount(1, $track1->genres);
        $this->assertEquals('Rock', $track1->genres->first()->name);
        
        $track2 = Track::where('title', 'Test Track 2')->first();
        $this->assertNotNull($track2, 'Track 2 was not found in the database');
        $this->assertCount(2, $track2->genres);
    }

    #[Test]
    public function testShow(): void
    {
        $track = Track::factory()->create();
        $response = $this->get(route('tracks.show', $track->id));
        $response->assertStatus(200);
        $response->assertViewIs('tracks.show');
        $response->assertViewHas('track');
        $this->assertEquals($track->id, $response->viewData('track')->id);
    }

    #[Test]
    public function testEdit(): void
    {
        $track = Track::factory()->create();
        $response = $this->get(route('tracks.edit', $track->id));
        $response->assertStatus(200);
        $response->assertViewIs('tracks.edit');
        $response->assertViewHas('track');
        $response->assertViewHas('genres');
    }

    #[Test]
    public function testUpdate(): void
    {
        $track = Track::factory()->create(['title' => 'Original Title']);
        $genre = Genre::factory()->create();
        
        $updateData = [
            'title' => 'Updated Title',
            'audio_url' => 'https:
            'image_url' => 'https:
            'duration' => '4:30',
            'genres' => $genre->name
        ];
        $response = $this->put(route('tracks.update', $track->id), $updateData);
        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tracks', [
            'id' => $track->id,
            'title' => 'Updated Title',
            'audio_url' => 'https:
        ]);
        $track->refresh();
        $this->assertCount(1, $track->genres);
        $this->assertEquals($genre->id, $track->genres->first()->id);
    }

    #[Test]
    public function testDestroy(): void
    {
        $track = Track::factory()->create();
        $response = $this->delete(route('tracks.destroy', $track->id));
        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('tracks', ['id' => $track->id]);
    }

    #[Test]
    public function testPlay(): void
    {
        $track = Track::factory()->create(['audio_url' => 'https:

        $response = $this->get(route('tracks.play', $track->id));
        $response->assertRedirect($track->audio_url);
    }
}
