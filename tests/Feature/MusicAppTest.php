<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MusicAppTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_dashboard_loads_successfully(): void
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_genres_page_loads_successfully(): void
    {
        $response = $this->get('/genres');
        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_create_genre(): void
    {
        $genreData = [
            'name' => 'Test Genre',
            'description' => 'This is a test genre',
        ];

        $response = $this->post('/genres', $genreData);

        $response->assertRedirect('/genres');
        $this->assertDatabaseHas('genres', ['name' => 'Test Genre']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_tracks_page_loads_successfully(): void
    {
        $response = $this->get('/tracks');
        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_create_track_with_genres(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $genre1 = Genre::factory()->create();
        $genre2 = Genre::factory()->create();

        $response = $this->post('/tracks', [
            'title' => 'New Track',
            'artist' => 'New Artist',
            'audio_url' => 'http://example.com/audio.mp3',
            'genre_ids' => [$genre1->id, $genre2->id]
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/tracks');
        $this->assertDatabaseHas('tracks', ['title' => 'New Track']);
        $track = Track::where('title', 'New Track')->first();
        $this->assertNotNull($track);
        $this->assertCount(2, $track->genres);
        $this->assertTrue($track->genres->contains($genre1));
        $this->assertTrue($track->genres->contains($genre2));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_playlists_page_loads_successfully(): void
    {
        $response = $this->get('/playlists');
        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_create_playlist(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $genre = Genre::factory()->create();
        $track1 = Track::factory()->create();
        $track2 = Track::factory()->create();

        $response = $this->post('/playlists', [
            'title' => 'My Awesome Playlist',
            'description' => 'A description',
            'genre_id' => $genre->id,
            'track_ids' => [$track1->id, $track2->id]
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/playlists');
        $this->assertDatabaseHas('playlists', ['title' => 'My Awesome Playlist']);
        $playlist = Playlist::where('title', 'My Awesome Playlist')->first();
        $this->assertNotNull($playlist);
        $this->assertCount(2, $playlist->tracks);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_add_tracks_to_playlist(): void
    {
        $genre = Genre::factory()->create();
        $tracks = Track::factory()->count(3)->create();
        $playlist = Playlist::factory()->create(['genre_id' => $genre->id]);
        $trackIds = $tracks->pluck('id')->toArray();

        $response = $this->post("/playlists/{$playlist->id}/tracks", [
            'track_ids' => $trackIds,
        ]);

        $response->assertRedirect("/playlists/{$playlist->id}");
        foreach ($trackIds as $trackId) {
            $this->assertDatabaseHas('playlist_track', [
                'playlist_id' => $playlist->id,
                'track_id' => $trackId,
            ]);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_search_tracks(): void
    {
        $rockTrack = Track::factory()->create(['title' => 'Rock Song']);
        $popTrack = Track::factory()->create(['title' => 'Pop Song']);
        $classicalTrack = Track::factory()->create(['title' => 'Classical Song']);
        $response = $this->get('/tracks?search=Rock');

        $response->assertStatus(200);
        $response->assertViewHas('tracks', function ($tracks) {
            return $tracks->contains('title', 'Rock Song');
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_filter_tracks_by_genre(): void
    {
        $rock = Genre::factory()->create(['name' => 'Rock']);
        $pop = Genre::factory()->create(['name' => 'Pop']);
        $rockTrack = Track::factory()->create(['title' => 'Rock Song']);
        $popTrack = Track::factory()->create(['title' => 'Pop Song']);
        $rockTrack->genres()->attach($rock->id);
        $popTrack->genres()->attach($pop->id);
        $response = $this->get("/tracks?genre={$rock->id}");

        $response->assertStatus(200);
        $response->assertViewHas('tracks', function ($tracks) {
            return $tracks->contains('title', 'Rock Song') && ! $tracks->contains('title', 'Pop Song');
        });
    }
}
