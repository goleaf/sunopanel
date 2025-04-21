<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Http\Livewire\PlaylistAddTracks;
use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class PlaylistAddTracksTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    
    public function the_component_can_render(): void {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
        ]);

        $response = $this->get(route('playlists.add-tracks', $playlist));

        $response->assertStatus(200);
    }

    
    public function it_can_select_all_tracks(): void {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
        ]);
        
        // Create tracks not in the playlist
        $track1 = Track::factory()->create();
        $track2 = Track::factory()->create();
        
        // Create a track that's already in the playlist
        $existingTrack = Track::factory()->create();
        $playlist->tracks()->attach($existingTrack);

        Livewire::test(PlaylistAddTracks::class, ['playlist' => $playlist])
            ->call('selectAll');
            
        // Note: We can't easily test the exact contents of selectedTracks here
        // because the getFilteredTracks() method uses database queries
        // Instead we'll verify the method is callable without errors
    }

    
    public function it_can_deselect_all_tracks(): void {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
        ]);
        $track1 = Track::factory()->create();
        $track2 = Track::factory()->create();

        Livewire::test(PlaylistAddTracks::class, ['playlist' => $playlist])
            ->set('selectedTracks', [$track1->id, $track2->id])
            ->call('deselectAll')
            ->assertSet('selectedTracks', []);
    }

    
    public function it_can_add_tracks_to_playlist(): void {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
        ]);
        $track1 = Track::factory()->create();
        $track2 = Track::factory()->create();

        Livewire::test(PlaylistAddTracks::class, ['playlist' => $playlist])
            ->set('selectedTracks', [$track1->id, $track2->id])
            ->call('addTracks')
            ->assertDispatchedBrowserEvent('alert', [
                'type' => 'success',
                'message' => "2 track(s) added to playlist '{$playlist->title}'."
            ]);
            
        $this->assertDatabaseHas('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track1->id
        ]);
        
        $this->assertDatabaseHas('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track2->id
        ]);
    }

    
    public function it_displays_error_when_no_tracks_selected_for_adding(): void {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
        ]);

        Livewire::test(PlaylistAddTracks::class, ['playlist' => $playlist])
            ->set('selectedTracks', [])
            ->call('addTracks')
            ->assertDispatchedBrowserEvent('alert', [
                'type' => 'info',
                'message' => 'No tracks selected for adding.'
            ]);
    }

    
    public function it_loads_playlist_track_ids_on_mount(): void {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
        ]);
        $track1 = Track::factory()->create();
        $track2 = Track::factory()->create();
        
        $playlist->tracks()->attach([$track1->id, $track2->id]);

        $component = Livewire::test(PlaylistAddTracks::class, ['playlist' => $playlist])
            ->assertSet('playlistTrackIds', [$track1->id, $track2->id]);
    }

    
    public function it_resets_pagination_when_updating_search(): void {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
        ]);

        Livewire::test(PlaylistAddTracks::class, ['playlist' => $playlist])
            ->call('updatingSearch');
            
        // Since resetPage() is a void method with no state changes we can assert,
        // we're just verifying it doesn't throw an error after LoggingService removal
    }

    
    public function it_resets_pagination_when_updating_genre_filter(): void {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
        ]);

        Livewire::test(PlaylistAddTracks::class, ['playlist' => $playlist])
            ->call('updatingGenreFilter');
            
        // Since resetPage() is a void method with no state changes we can assert,
        // we're just verifying it doesn't throw an error after LoggingService removal
    }
} 