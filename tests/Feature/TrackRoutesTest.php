<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $genre = Genre::factory()->create(['name' => 'Bubblegum bass']);
        $track = Track::factory()->create([
            'title' => 'Test Track',
            'audio_url' => 'https://example.com/setup_audio.mp3',
            'image_url' => 'https://example.com/setup_image.jpg',
        ]);

        $track->genres()->attach($genre->id);
    }

    #[Test]
public function tracks_index_page_loads_correctly()
    {
        $track = Track::first();
        $genre = Genre::where('name', 'Bubblegum bass')->first();

        $response = $this->get(route('tracks.index'));

        $response->assertStatus(200);
        $response->assertViewIs('tracks.index');
        $response->assertViewHas('tracks');
        $response->assertViewHas('tracks', function ($tracks) use ($track) {
            return $tracks->contains($track);
        });
    }

    #[Test]
public function track_show_page_loads_correctly()
    {
        $track = Track::first();

        $response = $this->get(route('tracks.show', $track));

        $response->assertStatus(200);
        $response->assertSee($track->title);
        $response->assertViewIs('tracks.show');
    }

    #[Test]
public function track_create_page_loads_correctly()
    {
        $response = $this->get(route('tracks.create'));

        $response->assertStatus(200);
        $response->assertSee('Add New Track');
        $response->assertViewIs('tracks.form');
    }

    #[Test]
public function track_edit_page_loads_correctly()
    {
        $track = Track::first();

        $response = $this->get(route('tracks.edit', $track));

        $response->assertStatus(200);
        $response->assertSee($track->title);
        $response->assertViewIs('tracks.form');
    }

    #[Test]
public function track_can_be_created()
    {
        $genre = Genre::first();

        $response = $this->post(route('tracks.store'), [
            'title' => 'New Test Track',
            'audio_url' => 'https://example.com/new_audio.mp3',
            'image_url' => 'https://example.com/new_image.jpg',
            'genre_ids' => [$genre->id],
        ]);

        $response->assertRedirect(route('tracks.index'));
        $this->assertDatabaseHas('tracks', ['title' => 'New Test Track']);

        $track = Track::where('title', 'New Test Track')->first();
        $this->assertTrue($track->genres->contains($genre->id));
    }

    #[Test]
public function track_can_be_updated()
    {
        $track = Track::first();
        $genre = Genre::first();

        $response = $this->put(route('tracks.update', $track), [
            'title' => 'Updated Track Title',
            'audio_url' => $track->audio_url,
            'image_url' => $track->image_url,
            'genre_ids' => [$genre->id],
        ]);

        $response->assertRedirect(route('tracks.index'));
        $this->assertDatabaseHas('tracks', ['id' => $track->id, 'title' => 'Updated Track Title']);
    }

    #[Test]
public function track_can_be_deleted()
    {
        $track = Track::first();

        $response = $this->delete(route('tracks.destroy', $track));

        $response->assertRedirect(route('tracks.index'));
        $this->assertDatabaseMissing('tracks', ['id' => $track->id]);
    }

    #[Test]
public function track_can_be_searched()
    {
        Track::factory()->create(['title' => 'Another Track', 'audio_url' => 'https://example.com/another_audio.mp3', 'image_url' => 'https://example.com/another_image.jpg']);

        $response = $this->get(route('tracks.index', ['search' => 'Test']));

        $response->assertStatus(200);
        $response->assertViewIs('tracks.index');
        $response->assertViewHas('tracks', function ($tracks) {
            return $tracks->contains('title', 'Test Track') && ! $tracks->contains('title', 'Another Track');
        });
    }

    #[Test]
public function tracks_can_be_filtered_by_genre()
    {
        $otherGenre = Genre::factory()->create(['name' => 'Rock']);
        $otherTrack = Track::factory()->create(['title' => 'Rock Track', 'audio_url' => 'https://example.com/rock_audio.mp3', 'image_url' => 'https://example.com/rock_image.jpg']);
        $otherTrack->genres()->attach($otherGenre->id);

        $bubblegumGenre = Genre::where('name', 'Bubblegum bass')->first();

        $response = $this->get(route('tracks.index', ['genre' => $bubblegumGenre->id]));

        $response->assertStatus(200);
        $response->assertViewIs('tracks.index');
        $response->assertViewHas('tracks', function ($tracks) {
            return $tracks->contains('title', 'Test Track') && ! $tracks->contains('title', 'Rock Track');
        });
    }

    #[Test]
public function play_method_redirects_to_audio_url()
    {
        $track = Track::first();

        $response = $this->get(route('tracks.play', $track));

        $response->assertRedirect($track->audio_url);
    }
}
