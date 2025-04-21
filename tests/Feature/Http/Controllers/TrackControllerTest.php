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
    public function test_Index(): void
    {
        Track::factory()->count(3)->create();
        $response = $this->get(route('tracks.index'));
        $response->assertStatus(200);
        $response->assertViewIs('tracks.index');
        $response->assertViewHas('tracks');
    }
    
    #[Test]
    public function test_IndexWithSearch(): void
    {
        Track::factory()->create(['title' => 'Test Track', 'audio_url' => 'https://example.com/search_audio1.mp3', 'image_url' => 'https://example.com/search_image1.jpg']);
        Track::factory()->create(['title' => 'Another Track', 'audio_url' => 'https://example.com/search_audio2.mp3', 'image_url' => 'https://example.com/search_image2.jpg']);
        $response = $this->get(route('tracks.index', ['search' => 'Test']));
        $response->assertStatus(200);
        $response->assertViewIs('tracks.index');
        $response->assertViewHas('tracks');
        $tracks = $response->viewData('tracks');
        $this->assertEquals(1, $tracks->count());
        $this->assertEquals('Test Track', $tracks->first()->title);
    }

    #[Test]
    public function test_Create(): void
    {
        Genre::factory()->count(3)->create();
        $response = $this->get(route('tracks.create'));
        $response->assertStatus(200);
        $response->assertViewIs('tracks.form');
        $response->assertViewHas('genres');
    }

    #[Test]
    public function test_Store(): void
    {
        $genre = Genre::factory()->create();
        $trackData = [
            'title' => 'New Test Track',
            'audio_url' => 'https://example.com/audio.mp3',
            'image_url' => 'https://example.com/image.jpg',
            'duration' => '3:45',
            'genre_ids' => [$genre->id]
        ];
        $response = $this->post(route('tracks.store'), $trackData);
        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tracks', [
            'title' => 'New Test Track',
            'audio_url' => 'https://example.com/audio.mp3'
        ]);
        $track = Track::where('title', 'New Test Track')->first();
        $this->assertCount(1, $track->genres);
        $this->assertEquals($genre->id, $track->genres->first()->id);
    }

    #[Test]
    public function test_ProcessBulkUpload(): void
    {
        $bulkData = [
            'bulk_tracks' => <<<EOD
Test Track 1|https://example.com/audio1.mp3|https://example.com/image1.jpg|Rock|3:30 
Test Track 2|https://example.com/audio2.mp3|https://example.com/image2.jpg|Pop,Jazz|4:00 
EOD
        ];

        $response = $this->post(route('tracks.bulk-upload'), $bulkData);
        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success');
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
    public function test_Show(): void
    {
        $track = Track::factory()->create(['audio_url' => 'https://example.com/show_audio.mp3', 'image_url' => 'https://example.com/show_image.jpg']);
        $response = $this->get(route('tracks.show', $track->id));
        $response->assertStatus(200);
        $response->assertViewIs('tracks.show');
        $response->assertViewHas('track');
        $this->assertEquals($track->id, $response->viewData('track')->id);
    }

    #[Test]
    public function test_Edit(): void
    {
        $track = Track::factory()->create(['audio_url' => 'https://example.com/edit_audio.mp3', 'image_url' => 'https://example.com/edit_image.jpg']);
        $response = $this->get(route('tracks.edit', $track->id));
        $response->assertStatus(200);
        $response->assertViewIs('tracks.form');
        $response->assertViewHas('track');
        $response->assertViewHas('genres');
    }

    #[Test]
    public function test_Update(): void
    {
        $track = Track::factory()->create(['title' => 'Original Title', 'audio_url' => 'https://example.com/orig_audio.mp3', 'image_url' => 'https://example.com/orig_image.jpg']);
        $genre = Genre::factory()->create();
        
        $updateData = [
            'title' => 'Updated Title',
            'audio_url' => 'https://example.com/new_audio.mp3',
            'image_url' => 'https://example.com/new_image.jpg',
            'duration' => '4:30',
            'genre_ids' => [$genre->id]
        ];
        $response = $this->put(route('tracks.update', $track->id), $updateData);
        $response->assertRedirect(route('tracks.show', $track));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tracks', [
            'id' => $track->id,
            'title' => 'Updated Title',
            'audio_url' => 'https://example.com/new_audio.mp3'
        ]);
        $track->refresh();
        $this->assertCount(1, $track->genres);
        $this->assertEquals($genre->id, $track->genres->first()->id);
    }

    #[Test]
    public function test_Destroy(): void
    {
        $track = Track::factory()->create(['audio_url' => 'https://example.com/del_audio.mp3', 'image_url' => 'https://example.com/del_image.jpg']);
        $response = $this->delete(route('tracks.destroy', $track->id));
        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('tracks', ['id' => $track->id]);
    }

    #[Test]
    public function test_Play(): void
    {
        $track = Track::factory()->create(['audio_url' => 'https://example.com/play_audio.mp3']);

        $response = $this->get(route('tracks.play', $track->id));
        $response->assertRedirect($track->audio_url);
    }
}
