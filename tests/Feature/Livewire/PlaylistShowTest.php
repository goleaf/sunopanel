<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Http\Livewire\PlaylistShow;
use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class PlaylistShowTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    #[\PHPUnit\Framework\Attributes\Test]
    public function the_component_can_render(): void {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
        ]);
        $track1 = Track::factory()->create();
        $track2 = Track::factory()->create();
        
        $playlist->tracks()->attach($track1);
        $playlist->tracks()->attach($track2);

        $response = $this->get(route('playlists.show', $playlist));

        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_select_all_tracks(): void {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
        ]);
        $track1 = Track::factory()->create();
        $track2 = Track::factory()->create();
        
        $playlist->tracks()->attach($track1);
        $playlist->tracks()->attach($track2);

        Livewire::test(PlaylistShow::class, ['playlist' => $playlist])
            ->call('selectAll')
            ->assertSet('selectedTracks', [$track1->id, $track2->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_deselect_all_tracks(): void {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
        ]);
        $track1 = Track::factory()->create();
        $track2 = Track::factory()->create();
        
        $playlist->tracks()->attach($track1);
        $playlist->tracks()->attach($track2);

        Livewire::test(PlaylistShow::class, ['playlist' => $playlist])
            ->set('selectedTracks', [$track1->id, $track2->id])
            ->call('deselectAll')
            ->assertSet('selectedTracks', []);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_remove_a_track(): void {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
        ]);
        $track = Track::factory()->create();
        
        $playlist->tracks()->attach($track);

        Livewire::test(PlaylistShow::class, ['playlist' => $playlist])
            ->call('removeTrack', $track->id)
            ->assertDispatchedBrowserEvent('alert', [
                'type' => 'success',
                'message' => 'Track removed from playlist successfully.'
            ]);
            
        $this->assertDatabaseMissing('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track->id
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_remove_selected_tracks(): void {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
        ]);
        $track1 = Track::factory()->create();
        $track2 = Track::factory()->create();
        
        $playlist->tracks()->attach($track1);
        $playlist->tracks()->attach($track2);

        Livewire::test(PlaylistShow::class, ['playlist' => $playlist])
            ->set('selectedTracks', [$track1->id, $track2->id])
            ->call('removeSelectedTracks')
            ->assertDispatchedBrowserEvent('alert', [
                'type' => 'success',
                'message' => '2 track(s) removed from playlist successfully.'
            ]);
            
        $this->assertDatabaseMissing('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track1->id
        ]);
        
        $this->assertDatabaseMissing('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track2->id
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_displays_error_when_no_tracks_selected_for_removal(): void {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
        ]);

        Livewire::test(PlaylistShow::class, ['playlist' => $playlist])
            ->set('selectedTracks', [])
            ->call('removeSelectedTracks')
            ->assertDispatchedBrowserEvent('alert', [
                'type' => 'info',
                'message' => 'No tracks selected for removal.'
            ]);
    }
} 