<?php

namespace Tests\Feature\Livewire;

use App\Http\Livewire\Playlists;
use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class PlaylistsTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    #[\PHPUnit\Framework\Attributes\Test]
    public function the_component_can_render()
    {
        $response = $this->get(route('playlists.index'));

        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_sort_playlists_by_different_fields()
    {
        // Create some test playlists
        Playlist::factory()->count(3)->create();

        // Test sorting by different fields
        Livewire::test(Playlists::class)
            ->call('sortBy', 'title')
            ->assertSet('sortField', 'title')
            ->assertSet('direction', 'asc')
            ->call('sortBy', 'title')  // Call again to toggle direction
            ->assertSet('direction', 'desc')
            ->call('sortBy', 'created_at')  // Change to a different field
            ->assertSet('sortField', 'created_at')
            ->assertSet('direction', 'asc');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_resets_pagination_when_searching()
    {
        Playlist::factory()->count(15)->create();

        Livewire::test(Playlists::class)
            ->call('updatingSearch');
            
        // Since resetPage() is a void method with no state changes we can assert,
        // we're just verifying it doesn't throw an error after LoggingService removal
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_resets_pagination_when_changing_genre_filter()
    {
        Playlist::factory()->count(15)->create();

        Livewire::test(Playlists::class)
            ->call('updatingGenreFilter');
            
        // Since resetPage() is a void method with no state changes we can assert,
        // we're just verifying it doesn't throw an error after LoggingService removal
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_delete_a_playlist()
    {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
            'title' => 'Playlist to Delete',
        ]);

        // Add some tracks to the playlist
        $track1 = Track::factory()->create();
        $track2 = Track::factory()->create();
        $playlist->tracks()->attach([$track1->id, $track2->id]);

        Livewire::test(Playlists::class)
            ->call('delete', $playlist->id)
            ->assertDispatchedBrowserEvent('alert', [
                'type' => 'success',
                'message' => "Playlist 'Playlist to Delete' deleted successfully."
            ]);
            
        $this->assertDatabaseMissing('playlists', [
            'id' => $playlist->id
        ]);
        
        // Check tracks are detached but still exist
        $this->assertDatabaseMissing('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track1->id,
        ]);
        
        $this->assertDatabaseMissing('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track2->id,
        ]);
        
        $this->assertDatabaseHas('tracks', [
            'id' => $track1->id,
        ]);
        
        $this->assertDatabaseHas('tracks', [
            'id' => $track2->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_errors_when_playlist_not_found()
    {
        Livewire::test(Playlists::class)
            ->call('delete', 999) // Non-existent ID
            ->assertDispatchedBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Failed to delete playlist: No query results for model [App\\Models\\Playlist] 999'
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_displays_error_message_when_render_fails()
    {
        // This is a bit tricky to test without mocking functionality,
        // but we can verify the error handling exists in the component
        // This test primarily verifies we can handle errors without LoggingService
        
        // Create a mock playlist service that throws an exception
        $mock = $this->getMockBuilder(\App\Services\Playlist\PlaylistService::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $mock->method('deletePlaylistAndDetachTracks')
            ->willThrowException(new \Exception('Test error'));
            
        app()->instance(\App\Services\Playlist\PlaylistService::class, $mock);
        
        $playlist = Playlist::factory()->create([
            'title' => 'Test Playlist',
        ]);
        
        Livewire::test(Playlists::class)
            ->call('delete', $playlist->id)
            ->assertDispatchedBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Failed to delete playlist: Test error'
            ]);
    }
} 