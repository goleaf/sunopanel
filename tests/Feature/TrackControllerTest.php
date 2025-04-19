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
        $response->assertSessionHas('success', 'Track created successfully.');
        
        $track = Track::where('title', 'New Test Track')->first();
        $this->assertNotNull($track);
        $this->assertEquals('https://example.com/new-audio.mp3', $track->audio_url);
        $this->assertEquals('https://example.com/new-image.jpg', $track->image_url);
        $this->assertEquals('3:30', $track->duration);
    }

    /**
     * Test validation for storing a new track.
     */
    public function test_track_store_validation()
    {
        $response = $this->post(route('tracks.store'), []);
        
        $response->assertSessionHasErrors(['title', 'audio_url', 'image_url']);
    }

    /**
     * Test the track edit form.
     */
    public function test_track_edit_form()
    {
        $track = Track::where('title', 'Test Track')->first();
        $this->assertNotNull($track);
        
        // Create genres with unique names and attach them to the track
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
        $response->assertSessionHas('success', 'Track updated successfully.');
        
        // Refresh track from database
        $track->refresh();
        
        $this->assertEquals('Updated Track', $track->title);
        $this->assertEquals('https://example.com/updated-audio.mp3', $track->audio_url);
        $this->assertEquals('https://example.com/updated-image.jpg', $track->image_url);
        $this->assertEquals('4:15', $track->duration);
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
            'genres' => 'Rock, Pop',
            'duration' => '3:00',
        ];
        
        $response = $this->put(route('tracks.update', $track->id), $updateData);
        
        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success', 'Track updated successfully.');
        
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
        $response->assertSessionHas('success', 'Track deleted successfully.');
        
        $this->assertNull(Track::find($track->id));
    }

    /**
     * Test playing a track with a URL.
     */
    public function test_track_play_with_url()
    {
        $track = Track::where('title', 'Test Track')->first();
        $this->assertNotNull($track);
        
        // Use url if available, otherwise fallback to audio_url
        $playUrl = $track->url ?? $track->audio_url;
        
        $response = $this->get(route('tracks.play', $track->id));
        
        $response->assertRedirect($playUrl);
    }

    /**
     * Test the track show page.
     */
    public function test_track_show()
    {
        $track = Track::where('title', 'Test Track')->first();
        $this->assertNotNull($track);
        
        // Create genres with unique names and attach them to the track
        $genres = [];
        $genreNames = ['Progressive Rock', 'Indie Rock'];
        
        foreach ($genreNames as $name) {
            $genres[] = Genre::firstOrCreate(['name' => $name]);
        }
        
        $track->genres()->attach(collect($genres)->pluck('id'));
        
        // Create playlists and attach the track
        $playlists = [];
        for ($i = 1; $i <= 2; $i++) {
            $playlists[] = Playlist::create([
                'title' => "Test Playlist $i",
                'description' => "A playlist for testing"
            ]);
        }
        
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
        // Create test data - tracks with different names
        $rockTrack1 = Track::factory()->create(['title' => 'Rock Song 1']);
        $rockTrack2 = Track::factory()->create(['title' => 'Rock Track']);
        $popTrack1 = Track::factory()->create(['title' => 'Pop Song 1']);
        
        // Search for tracks with "Rock" in the title
        $response = $this->get('/tracks?search=Rock');
        
        $response->assertStatus(200);
        $response->assertViewIs('tracks.index');
        $response->assertViewHas('tracks');
        
        // Check that the right tracks are in the results
        $response->assertViewHas('tracks', function($tracks) use ($rockTrack1, $rockTrack2, $popTrack1) {
            return $tracks->contains('id', $rockTrack1->id) && 
                   $tracks->contains('id', $rockTrack2->id) && 
                   !$tracks->contains('id', $popTrack1->id);
        });
    }

    /**
     * Test filtering tracks by genre.
     */
    public function test_track_filter_by_genre()
    {
        // Create or get test genres
        $rockGenre = Genre::firstOrCreate(['slug' => 'rock'], ['name' => 'Rock', 'description' => 'Rock genre for testing']);
        $popGenre = Genre::firstOrCreate(['slug' => 'pop'], ['name' => 'Pop', 'description' => 'Pop genre for testing']);
        
        // Create test tracks with different genres
        $rockTrack1 = Track::factory()->create(['title' => 'Rock Song 1']);
        $rockTrack2 = Track::factory()->create(['title' => 'Rock Track']);
        $popTrack = Track::factory()->create(['title' => 'Pop Song 1']);
        
        // Associate tracks with genres
        $rockTrack1->genres()->attach($rockGenre);
        $rockTrack2->genres()->attach($rockGenre);
        $popTrack->genres()->attach($popGenre);
        
        // Filter tracks by Rock genre
        $response = $this->get('/tracks?genre=' . $rockGenre->id);
        
        $response->assertStatus(200);
        $response->assertViewIs('tracks.index');
        $response->assertViewHas('tracks');
        
        // Check that the right tracks are in the results
        $response->assertViewHas('tracks', function($tracks) use ($rockTrack1, $rockTrack2, $popTrack) {
            return $tracks->contains('id', $rockTrack1->id) && 
                   $tracks->contains('id', $rockTrack2->id) && 
                   !$tracks->contains('id', $popTrack->id);
        });
    }

    /**
     * Test bulk upload of tracks.
     */
    public function test_track_bulk_upload()
    {
        $bulkData = "Bulk Track 1|https://example.com/audio1.mp3|https://example.com/image1.jpg|Rock, Pop\nBulk Track 2|https://example.com/audio2.mp3|https://example.com/image2.jpg|Electronic";
        
        $response = $this->post(route('tracks.bulk-upload'), [
            'bulk_tracks' => $bulkData
        ]);
        
        $response->assertRedirect(route('tracks.index'));
        $response->assertSessionHas('success');
        
        // Check if tracks were created
        $track1 = Track::where('title', 'Bulk Track 1')->first();
        $track2 = Track::where('title', 'Bulk Track 2')->first();
        
        $this->assertNotNull($track1);
        $this->assertNotNull($track2);
        
        // Check URLs
        $this->assertEquals('https://example.com/audio1.mp3', $track1->audio_url);
        $this->assertEquals('https://example.com/image1.jpg', $track1->image_url);
        
        $this->assertEquals('https://example.com/audio2.mp3', $track2->audio_url);
        $this->assertEquals('https://example.com/image2.jpg', $track2->image_url);
        
        // Check genres
        $this->assertEquals(2, $track1->genres()->count());
        $this->assertEquals(1, $track2->genres()->count());
        
        $this->assertTrue($track1->genres->contains('name', 'Rock'));
        $this->assertTrue($track1->genres->contains('name', 'Pop'));
        $this->assertTrue($track2->genres->contains('name', 'Electronic'));
    }
} 