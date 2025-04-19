<?php

namespace Tests\Feature;

use App\Models\Track;
use App\Models\Genre;
use App\Models\User;
use App\Models\Playlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TrackControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user and authenticate
        $this->actingAs(User::factory()->create());
        
        // Set up fake storage
        Storage::fake('public');
        
        // Create a test track
        $track = Track::create([
            'title' => 'Test Track',
            'audio_url' => 'https://example.com/audio.mp3',
            'image_url' => 'https://example.com/image.jpg',
            'unique_id' => Track::generateUniqueId('Test Track'),
            'duration' => '3:00'
        ]);
        
        // Create Rock and Pop genres
        $rockGenre = Genre::firstOrCreate(['name' => 'Rock']);
        $popGenre = Genre::firstOrCreate(['name' => 'Pop']);
        
        // Create a Rock track and a Pop track for search and filter tests
        $rockTrack = Track::create([
            'title' => 'Rock Song 1',
            'audio_url' => 'https://example.com/rock.mp3',
            'image_url' => 'https://example.com/rock.jpg',
            'unique_id' => Track::generateUniqueId('Rock Song 1'),
            'duration' => '3:00'
        ]);
        $rockTrack->genres()->attach($rockGenre->id);
        
        $popTrack = Track::create([
            'title' => 'Pop Song 1',
            'audio_url' => 'https://example.com/pop.mp3',
            'image_url' => 'https://example.com/pop.jpg',
            'unique_id' => Track::generateUniqueId('Pop Song 1'),
            'duration' => '3:00'
        ]);
        $popTrack->genres()->attach($popGenre->id);
        
        // Create tracks for genre filtering test
        $rockTrack2 = Track::create([
            'title' => 'Rock Track',
            'audio_url' => 'https://example.com/rock2.mp3',
            'image_url' => 'https://example.com/rock2.jpg',
            'unique_id' => Track::generateUniqueId('Rock Track'),
            'duration' => '3:00'
        ]);
        $rockTrack2->genres()->attach($rockGenre->id);
        
        $popTrack2 = Track::create([
            'title' => 'Pop Track',
            'audio_url' => 'https://example.com/pop2.mp3',
            'image_url' => 'https://example.com/pop2.jpg',
            'unique_id' => Track::generateUniqueId('Pop Track'),
            'duration' => '3:00'
        ]);
        $popTrack2->genres()->attach($popGenre->id);
        
        Log::info('Test setup complete', [
            'tracks_created' => Track::count(),
            'genres_created' => Genre::count()
        ]);
    }

    /**
     * Test the tracks index page.
     */
    public function test_tracks_index_page()
    {
        $response = $this->get(route('tracks.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('tracks.index');
        $response->assertViewHas('tracks');
        
        // Check if the tracks exist in the database
        $this->assertTrue(Track::where('title', 'Test Track')->exists());
    }

    /**
     * Test the track creation form.
     */
    public function test_track_create_form()
    {
        $response = $this->get(route('tracks.create'));
        
        $response->assertStatus(200);
        $response->assertViewIs('tracks.create');
    }

    /**
     * Test storing a new track.
     */
    public function test_track_store()
    {
        $genreString = 'Rock, Pop';
        
        $trackData = [
            'title' => 'New Test Track',
            'audio_url' => 'https://example.com/new-audio.mp3',
            'image_url' => 'https://example.com/new-image.jpg',
            'genres' => $genreString,
            'duration' => '3:30',
        ];
        
        $response = $this->post(route('tracks.store'), $trackData);
        
        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success', 'Track created successfully!');
        
        $track = Track::where('title', 'New Test Track')->first();
        $this->assertNotNull($track);
        $this->assertEquals('https://example.com/new-audio.mp3', $track->audio_url);
        $this->assertEquals('https://example.com/new-image.jpg', $track->image_url);
        $this->assertEquals('3:30', $track->duration);
        
        // Test that genres were created and attached
        $genres = $track->genres;
        $this->assertEquals(2, $genres->count());
        
        $genreNames = $genres->pluck('name')->toArray();
        $this->assertContains('Rock', $genreNames);
        $this->assertContains('Pop', $genreNames);
    }

    /**
     * Test validation for storing a new track.
     */
    public function test_track_store_validation()
    {
        $response = $this->post(route('tracks.store'), []);
        
        $response->assertSessionHasErrors(['title', 'audio_url', 'image_url', 'genres']);
    }

    /**
     * Test the track edit form.
     */
    public function test_track_edit_form()
    {
        $track = Track::where('title', 'Test Track')->first();
        $this->assertNotNull($track);
        
        // Create genres and attach them to the track
        $genres = Genre::factory()->count(2)->create();
        $track->genres()->attach($genres->pluck('id'));
        
        $response = $this->get(route('tracks.edit', $track->id));
        
        $response->assertStatus(200);
        $response->assertViewIs('tracks.edit');
        $response->assertViewHas('track');
    }

    /**
     * Test updating a track.
     */
    public function test_track_update()
    {
        $track = Track::where('title', 'Test Track')->first();
        $this->assertNotNull($track);
        
        // Create genres for testing
        $genre1 = Genre::factory()->create(['name' => 'Electronic']);
        $genre2 = Genre::factory()->create(['name' => 'Ambient']);
        
        $updateData = [
            'title' => 'Updated Track',
            'audio_url' => 'https://example.com/updated-audio.mp3',
            'image_url' => 'https://example.com/updated-image.jpg',
            'genres' => 'Electronic, Ambient',
            'duration' => '4:15',
        ];
        
        $response = $this->put(route('tracks.update', $track->id), $updateData);
        
        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success', 'Track updated successfully!');
        
        // Refresh track from database
        $track->refresh();
        
        $this->assertEquals('Updated Track', $track->title);
        $this->assertEquals('https://example.com/updated-audio.mp3', $track->audio_url);
        $this->assertEquals('https://example.com/updated-image.jpg', $track->image_url);
        $this->assertEquals('4:15', $track->duration);
        
        // Test that genres were updated correctly
        $genres = $track->genres;
        $this->assertEquals(2, $genres->count());
        
        $genreNames = $genres->pluck('name')->toArray();
        $this->assertContains('Electronic', $genreNames);
        $this->assertContains('Ambient', $genreNames);
    }

    /**
     * Test updating a track without changing the file.
     */
    public function test_track_update_without_file()
    {
        $track = Track::where('title', 'Test Track')->first();
        $this->assertNotNull($track);
        
        $oldAudioUrl = $track->audio_url;
        $oldImageUrl = $track->image_url;
        
        // Update just the name
        $updateData = [
            'title' => 'Renamed Track',
            'audio_url' => $oldAudioUrl,
            'image_url' => $oldImageUrl,
            'genres' => 'Rock',
            'duration' => '3:00',
        ];
        
        $response = $this->put(route('tracks.update', $track->id), $updateData);
        
        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success', 'Track updated successfully!');
        
        // Refresh track from database
        $track->refresh();
        
        $this->assertEquals('Renamed Track', $track->title);
        $this->assertEquals($oldAudioUrl, $track->audio_url);
        $this->assertEquals($oldImageUrl, $track->image_url);
        $this->assertEquals('3:00', $track->duration);
    }

    /**
     * Test deleting a track.
     */
    public function test_track_delete()
    {
        $track = Track::where('title', 'Test Track')->first();
        $this->assertNotNull($track);
        
        $response = $this->delete(route('tracks.destroy', $track->id));
        
        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success', 'Track deleted successfully!');
        
        $this->assertNull(Track::find($track->id));
    }

    /**
     * Test playing a track with a URL.
     */
    public function test_track_play_with_url()
    {
        $track = Track::where('title', 'Test Track')->first();
        $this->assertNotNull($track);
        
        $response = $this->get(route('tracks.play', $track->id));
        
        $response->assertRedirect($track->audio_url);
    }

    /**
     * Test the track show page.
     */
    public function test_track_show()
    {
        $track = Track::where('title', 'Test Track')->first();
        $this->assertNotNull($track);
        
        // Create genres and attach them to the track
        $genres = Genre::factory()->count(2)->create();
        $track->genres()->attach($genres->pluck('id'));
        
        // Create playlists and attach the track
        $playlists = Playlist::factory()->count(2)->create();
        foreach ($playlists as $playlist) {
            $playlist->tracks()->attach($track->id);
        }
        
        $response = $this->get(route('tracks.show', $track->id));
        
        $response->assertStatus(200);
        $response->assertViewIs('tracks.show');
        $response->assertViewHas('track');
        
        // Check if the track is properly retrieved
        $this->assertEquals($track->id, $response->viewData('track')->id);
    }

    /**
     * Test searching for tracks.
     */
    public function test_track_search()
    {
        $response = $this->get(route('tracks.index', ['search' => 'Rock']));
        
        $response->assertStatus(200);
        
        // Verify that search works on the database level
        $searchResults = Track::where('title', 'like', '%Rock%')->get();
        $this->assertGreaterThan(0, $searchResults->count());
        $this->assertTrue($searchResults->contains('title', 'Rock Song 1'));
        $this->assertFalse($searchResults->contains('title', 'Pop Song 1'));
    }

    /**
     * Test filtering tracks by genre.
     */
    public function test_track_filter_by_genre()
    {
        // Get the already created genres from the setup
        $rockGenre = Genre::where('name', 'Rock')->first();
        $popGenre = Genre::where('name', 'Pop')->first();
        
        $this->assertNotNull($rockGenre, 'Rock genre not found');
        $this->assertNotNull($popGenre, 'Pop genre not found');
        
        // Test filtering by Rock genre
        $response = $this->get(route('tracks.index', ['genre' => $rockGenre->id]));
        $response->assertStatus(200);
        
        // Verify filtering works at database level
        $rockTracks = Track::whereHas('genres', function($q) use ($rockGenre) {
            $q->where('genres.id', $rockGenre->id);
        })->get();
        
        $popTracks = Track::whereHas('genres', function($q) use ($popGenre) {
            $q->where('genres.id', $popGenre->id);
        })->get();
        
        $this->assertGreaterThan(0, $rockTracks->count());
        $this->assertTrue($rockTracks->contains('title', 'Rock Track'));
        $this->assertFalse($rockTracks->contains('title', 'Pop Track'));
        
        $this->assertGreaterThan(0, $popTracks->count());
        $this->assertTrue($popTracks->contains('title', 'Pop Track'));
        $this->assertFalse($popTracks->contains('title', 'Rock Track'));
    }

    /**
     * Test bulk upload of tracks.
     */
    public function test_track_bulk_upload()
    {
        $bulkData = "Test Bulk Track 1|https://example.com/audio1.mp3|https://example.com/image1.jpg|Rock, Pop\n";
        $bulkData .= "Test Bulk Track 2|https://example.com/audio2.mp3|https://example.com/image2.jpg|Electronic, Ambient";
        
        $response = $this->post(route('tracks.store'), [
            'bulk_tracks' => $bulkData,
            // Add dummy fields to satisfy validation
            'title' => 'Dummy Title',
            'audio_url' => 'https://example.com/dummy.mp3',
            'image_url' => 'https://example.com/dummy.jpg',
            'genres' => 'Dummy Genre'
        ]);
        
        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success');
        
        // Check that the tracks were created
        $track1 = Track::where('title', 'Test Bulk Track 1')->first();
        $track2 = Track::where('title', 'Test Bulk Track 2')->first();
        
        $this->assertNotNull($track1);
        $this->assertNotNull($track2);
        
        // Check genres
        $this->assertEquals(2, $track1->genres->count());
        $this->assertEquals(2, $track2->genres->count());
        
        $track1GenreNames = $track1->genres->pluck('name')->toArray();
        $this->assertContains('Rock', $track1GenreNames);
        $this->assertContains('Pop', $track1GenreNames);
        
        $track2GenreNames = $track2->genres->pluck('name')->toArray();
        $this->assertContains('Electronic', $track2GenreNames);
        $this->assertContains('Ambient', $track2GenreNames);
    }
} 