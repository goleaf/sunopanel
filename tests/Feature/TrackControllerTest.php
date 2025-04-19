<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrackControllerTest extends TestCase
{
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        Storage::fake('public');
        $track = Track::create([
            'title' => 'Test Track',
            'audio_url' => 'https:
            'image_url' => 'https:
            'unique_id' => Track::generateUniqueId('Test Track'),
            'duration' => '3:00',
        ]);
        $rockGenre = Genre::firstOrCreate(['name' => 'Rock']);
        $popGenre = Genre::firstOrCreate(['name' => 'Pop']);
        $rockTrack = Track::create([
            'title' => 'Rock Song 1',
            'audio_url' => 'https:
            'image_url' => 'https:
            'unique_id' => Track::generateUniqueId('Rock Song 1'),
            'duration' => '3:00',
        ]);
        $rockTrack->genres()->attach($rockGenre->id);

        $popTrack = Track::create([
            'title' => 'Pop Song 1',
            'audio_url' => 'https:
            'image_url' => 'https:
            'unique_id' => Track::generateUniqueId('Pop Song 1'),
            'duration' => '3:00',
        ]);
        $popTrack->genres()->attach($popGenre->id);
        $rockTrack2 = Track::create([
            'title' => 'Rock Track',
            'audio_url' => 'https:
            'image_url' => 'https:
            'unique_id' => Track::generateUniqueId('Rock Track'),
            'duration' => '3:00',
        ]);
        $rockTrack2->genres()->attach($rockGenre->id);

        $popTrack2 = Track::create([
            'title' => 'Pop Track',
            'audio_url' => 'https:
            'image_url' => 'https:
            'unique_id' => Track::generateUniqueId('Pop Track'),
            'duration' => '3:00',
        ]);
        $popTrack2->genres()->attach($popGenre->id);

        Log::info('Test setup complete', [
            'tracks_created' => Track::count(),
            'genres_created' => Genre::count(),
        ]);
    }

    public function test_tracks_index_page()
    {
        $response = $this->get(route('tracks.index'));

        $response->assertStatus(200);
        $response->assertViewIs('tracks.index');
        $response->assertViewHas('tracks');
        $this->assertTrue(Track::where('title', 'Test Track')->exists());
    }

    public function test_track_create_form()
    {
        $response = $this->get(route('tracks.create'));

        $response->assertStatus(200);
        $response->assertViewIs('tracks.create');
    }

    public function test_track_store()
    {
        $genreString = 'Rock, Pop';

        $trackData = [
            'title' => 'New Test Track',
            'audio_url' => 'https:
            'image_url' => 'https:
            'genres' => $genreString,
            'duration' => '3:30',
        ];

        $response = $this->post(route('tracks.store'), $trackData);

        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success', 'Track created successfully.');

        $track = Track::where('title', 'New Test Track')->first();
        $this->assertNotNull($track);
        $this->assertEquals('https:
        $this->assertEquals('https:
        $this->assertEquals('3:30', $track->duration);
    }

    public function test_track_store_validation()
    {
        $response = $this->post(route('tracks.store'), []);

        $response->assertSessionHasErrors(['title', 'audio_url', 'image_url']);
    }

    public function test_track_edit_form()
    {
        $track = Track::where('title', 'Test Track')->first();
        $this->assertNotNull($track);
        $genres = [];
        $genreNames = ['Alternative Rock', 'Art Rock'];

        foreach ($genreNames as $name) {
            $genres[] = Genre::firstOrCreate(['name' => $name]);
        }

        $track->genres()->attach(collect($genres)->pluck('id'));

        $response = $this->get(route('tracks.edit', $track->id));

        $response->assertStatus(200);
        $response->assertViewIs('tracks.edit');
        $response->assertViewHas('track');
    }

    public function test_track_update()
    {
        $track = Track::where('title', 'Test Track')->first();
        $this->assertNotNull($track);
        $genre1 = Genre::factory()->create(['name' => 'Electronic']);
        $genre2 = Genre::factory()->create(['name' => 'Ambient']);

        $updateData = [
            'title' => 'Updated Track',
            'audio_url' => 'https:
            'image_url' => 'https:
            'genres' => 'Electronic, Ambient',
            'duration' => '4:15',
        ];

        $response = $this->put(route('tracks.update', $track->id), $updateData);

        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success', 'Track updated successfully.');
        $track->refresh();

        $this->assertEquals('Updated Track', $track->title);
        $this->assertEquals('https:
        $this->assertEquals('https:
        $this->assertEquals('4:15', $track->duration);
    }

    public function test_track_update_without_file()
    {
        $track = Track::where('title', 'Test Track')->first();
        $this->assertNotNull($track);

        $oldAudioUrl = $track->audio_url;
        $oldImageUrl = $track->image_url;
        $updateData = [
            'title' => 'Renamed Track',
            'audio_url' => $oldAudioUrl,
            'image_url' => $oldImageUrl,
            'genres' => 'Rock, Pop',
            'duration' => '3:00',
        ];

        $response = $this->put(route('tracks.update', $track->id), $updateData);

        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success', 'Track updated successfully.');
        $track->refresh();

        $this->assertEquals('Renamed Track', $track->title);
        $this->assertEquals($oldAudioUrl, $track->audio_url);
        $this->assertEquals($oldImageUrl, $track->image_url);
        $this->assertEquals('3:00', $track->duration);
    }

    public function test_track_delete()
    {
        $track = Track::where('title', 'Test Track')->first();
        $this->assertNotNull($track);

        $response = $this->delete(route('tracks.destroy', $track->id));

        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success', 'Track deleted successfully.');

        $this->assertNull(Track::find($track->id));
    }

    public function test_track_play_with_url()
    {
        $track = Track::where('title', 'Test Track')->first();
        $this->assertNotNull($track);
        $playUrl = $track->url ?? $track->audio_url;

        $response = $this->get(route('tracks.play', $track->id));

        $response->assertRedirect($playUrl);
    }

    public function test_track_show()
    {
        $track = Track::where('title', 'Test Track')->first();
        $this->assertNotNull($track);
        $genres = [];
        $genreNames = ['Progressive Rock', 'Indie Rock'];

        foreach ($genreNames as $name) {
            $genres[] = Genre::firstOrCreate(['name' => $name]);
        }

        $track->genres()->attach(collect($genres)->pluck('id'));
        $playlists = [];
        for ($i = 1; $i <= 2; $i++) {
            $playlists[] = Playlist::create([
                'title' => "Test Playlist $i",
                'description' => 'A playlist for testing',
            ]);
        }

        foreach ($playlists as $playlist) {
            $playlist->tracks()->attach($track->id);
        }

        $response = $this->get(route('tracks.show', $track->id));

        $response->assertStatus(200);
        $response->assertViewIs('tracks.show');
        $response->assertViewHas('track');
        $this->assertEquals($track->id, $response->viewData('track')->id);
    }

    public function test_track_search()
    {
        $rockTrack1 = Track::factory()->create(['title' => 'Rock Song 1']);
        $rockTrack2 = Track::factory()->create(['title' => 'Rock Track']);
        $popTrack1 = Track::factory()->create(['title' => 'Pop Song 1']);
        $response = $this->get('/tracks?search=Rock');

        $response->assertStatus(200);
        $response->assertViewIs('tracks.index');
        $response->assertViewHas('tracks');
        $response->assertViewHas('tracks', function ($tracks)         });
    }

    public function test_track_filter_by_genre()
    {
        $rockGenre = Genre::firstOrCreate(['slug' => 'rock'], ['name' => 'Rock', 'description' => 'Rock genre for testing']);
        $popGenre = Genre::firstOrCreate(['slug' => 'pop'], ['name' => 'Pop', 'description' => 'Pop genre for testing']);
        $rockTrack1 = Track::factory()->create(['title' => 'Rock Song 1']);
        $rockTrack2 = Track::factory()->create(['title' => 'Rock Track']);
        $popTrack = Track::factory()->create(['title' => 'Pop Song 1']);
        $rockTrack1->genres()->attach($rockGenre);
        $rockTrack2->genres()->attach($rockGenre);
        $popTrack->genres()->attach($popGenre);
        $response = $this->get('/tracks?genre='.$rockGenre->id);

        $response->assertStatus(200);
        $response->assertViewIs('tracks.index');
        $response->assertViewHas('tracks');
        $response->assertViewHas('tracks', function ($tracks)         });
    }

    public function test_track_bulk_upload()
    {
        $bulkData = "Bulk Track 1|https:

        $response = $this->post(route('tracks.bulk-upload'), [
            'bulk_tracks' => $bulkData,
        ]);

        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success');
        $track1 = Track::where('title', 'Bulk Track 1')->first();
        $track2 = Track::where('title', 'Bulk Track 2')->first();

        $this->assertNotNull($track1);
        $this->assertNotNull($track2);
        $this->assertEquals('https:
        $this->assertEquals('https:

        $this->assertEquals('https:
        $this->assertEquals('https:

        $this->assertEquals(2, $track1->genres()->count());
        $this->assertEquals(1, $track2->genres()->count());

        $this->assertTrue($track1->genres->contains('name', 'Rock'));
        $this->assertTrue($track1->genres->contains('name', 'Pop'));
        $this->assertTrue($track2->genres->contains('name', 'Electronic'));
    }
}
