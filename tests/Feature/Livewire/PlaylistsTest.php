<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Http\Livewire\Playlists;
use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class PlaylistsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Genre $genre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->genre = Genre::factory()->create(['name' => 'Test Genre']);
        Auth::login($this->user);
    }

    /** @test */
    public function the_component_can_render()
    {
        $component = Livewire::test(Playlists::class);
        $component->assertStatus(200);
    }

    /** @test */
    public function it_can_load_playlists()
    {
        $playlist1 = Playlist::factory()->create([
            'name' => 'Test Playlist 1',
            'user_id' => $this->user->id
        ]);
        
        $playlist2 = Playlist::factory()->create([
            'name' => 'Test Playlist 2',
            'user_id' => $this->user->id
        ]);

        Livewire::test(Playlists::class)
            ->assertSee('Test Playlist 1')
            ->assertSee('Test Playlist 2');
    }

    /** @test */
    public function it_can_search_playlists()
    {
        $playlist1 = Playlist::factory()->create([
            'name' => 'Rock Playlist',
            'user_id' => $this->user->id
        ]);
        
        $playlist2 = Playlist::factory()->create([
            'name' => 'Pop Playlist',
            'user_id' => $this->user->id
        ]);

        Livewire::test(Playlists::class)
            ->set('search', 'Rock')
            ->assertSee('Rock Playlist')
            ->assertDontSee('Pop Playlist');
    }

    /** @test */
    public function it_can_filter_playlists_by_user()
    {
        $anotherUser = User::factory()->create();
        
        $playlist1 = Playlist::factory()->create([
            'name' => 'My Playlist',
            'user_id' => $this->user->id
        ]);
        
        $playlist2 = Playlist::factory()->create([
            'name' => 'Other User Playlist',
            'user_id' => $anotherUser->id
        ]);

        Livewire::test(Playlists::class)
            ->set('userId', $this->user->id)
            ->assertSee('My Playlist')
            ->assertDontSee('Other User Playlist');
    }

    /** @test */
    public function it_can_sort_playlists()
    {
        $playlistA = Playlist::factory()->create([
            'name' => 'A Playlist',
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2)
        ]);
        
        $playlistB = Playlist::factory()->create([
            'name' => 'B Playlist',
            'user_id' => $this->user->id,
            'created_at' => now()->subDay()
        ]);
        
        $playlistC = Playlist::factory()->create([
            'name' => 'C Playlist',
            'user_id' => $this->user->id,
            'created_at' => now()
        ]);

        // Test name ascending sort
        $component = Livewire::test(Playlists::class)
            ->set('sortField', 'name')
            ->set('sortDirection', 'asc');
        
        $html = $component->payload['effects']['html'];
        $this->assertTrue(strpos($html, 'A Playlist') < strpos($html, 'B Playlist'));
        $this->assertTrue(strpos($html, 'B Playlist') < strpos($html, 'C Playlist'));

        // Test name descending sort
        $component = Livewire::test(Playlists::class)
            ->set('sortField', 'name')
            ->set('sortDirection', 'desc');
        
        $html = $component->payload['effects']['html'];
        $this->assertTrue(strpos($html, 'C Playlist') < strpos($html, 'B Playlist'));
        $this->assertTrue(strpos($html, 'B Playlist') < strpos($html, 'A Playlist'));

        // Test created_at descending sort
        $component = Livewire::test(Playlists::class)
            ->set('sortField', 'created_at')
            ->set('sortDirection', 'desc');
        
        $html = $component->payload['effects']['html'];
        $this->assertTrue(strpos($html, 'C Playlist') < strpos($html, 'B Playlist'));
        $this->assertTrue(strpos($html, 'B Playlist') < strpos($html, 'A Playlist'));
    }

    /** @test */
    public function it_can_paginate_playlists()
    {
        // Create 15 playlists (assuming per_page is 10)
        Playlist::factory()->count(15)->create(['user_id' => $this->user->id]);

        $component = Livewire::test(Playlists::class);
        
        // Should show pagination links
        $component->assertSeeHtml('wire:click="nextPage"');
        
        // Should show the correct number of items on first page
        $this->assertEquals(10, substr_count($component->payload['effects']['html'], 'class="playlist-item"'));
        
        // Go to next page
        $component->call('nextPage');
        
        // Should now see 5 items
        $this->assertEquals(5, substr_count($component->payload['effects']['html'], 'class="playlist-item"'));
    }

    /** @test */
    public function it_can_create_a_new_playlist()
    {
        $playlistName = 'New Test Playlist';
        $playlistDescription = 'This is a test playlist description';

        Livewire::test(Playlists::class)
            ->set('name', $playlistName)
            ->set('description', $playlistDescription)
            ->call('storePlaylist')
            ->assertEmitted('refresh-playlists');

        $this->assertDatabaseHas('playlists', [
            'name' => $playlistName,
            'description' => $playlistDescription,
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_validates_playlist_name_uniqueness_for_user()
    {
        Playlist::factory()->create([
            'name' => 'Existing Playlist',
            'user_id' => $this->user->id
        ]);

        Livewire::test(Playlists::class)
            ->set('name', 'Existing Playlist')
            ->set('description', 'This should fail validation')
            ->call('storePlaylist')
            ->assertHasErrors(['name' => 'unique']);
    }

    /** @test */
    public function it_can_update_a_playlist()
    {
        $playlist = Playlist::factory()->create([
            'name' => 'Original Playlist',
            'description' => 'Original description',
            'user_id' => $this->user->id
        ]);

        $newName = 'Updated Playlist';
        $newDescription = 'Updated description';

        Livewire::test(Playlists::class)
            ->call('editPlaylist', $playlist->id)
            ->assertSet('playlistId', $playlist->id)
            ->assertSet('name', 'Original Playlist')
            ->assertSet('description', 'Original description')
            ->set('name', $newName)
            ->set('description', $newDescription)
            ->call('updatePlaylist')
            ->assertEmitted('refresh-playlists');

        $this->assertDatabaseHas('playlists', [
            'id' => $playlist->id,
            'name' => $newName,
            'description' => $newDescription
        ]);
    }

    /** @test */
    public function it_can_delete_a_playlist()
    {
        $playlist = Playlist::factory()->create([
            'name' => 'Playlist to Delete',
            'user_id' => $this->user->id
        ]);

        Livewire::test(Playlists::class)
            ->call('confirmDelete', $playlist->id)
            ->assertSet('playlistIdToDelete', $playlist->id)
            ->assertSet('showDeleteModal', true)
            ->call('deletePlaylist')
            ->assertSet('showDeleteModal', false);

        $this->assertDatabaseMissing('playlists', [
            'id' => $playlist->id,
        ]);
    }

    /** @test */
    public function it_can_cancel_playlist_deletion()
    {
        $playlist = Playlist::factory()->create([
            'name' => 'Playlist Not To Delete',
            'user_id' => $this->user->id
        ]);

        Livewire::test(Playlists::class)
            ->call('confirmDelete', $playlist->id)
            ->assertSet('playlistIdToDelete', $playlist->id)
            ->assertSet('showDeleteModal', true)
            ->call('cancelDelete')
            ->assertSet('playlistIdToDelete', null)
            ->assertSet('showDeleteModal', false);

        $this->assertDatabaseHas('playlists', [
            'id' => $playlist->id,
        ]);
    }

    /** @test */
    public function it_can_add_tracks_to_a_playlist()
    {
        $playlist = Playlist::factory()->create([
            'user_id' => $this->user->id
        ]);
        
        $track1 = Track::factory()->create([
            'genre_id' => $this->genre->id
        ]);
        
        $track2 = Track::factory()->create([
            'genre_id' => $this->genre->id
        ]);

        Livewire::test(Playlists::class)
            ->call('addTracksToPlaylist', $playlist->id, [$track1->id, $track2->id])
            ->assertEmitted('refresh-playlists');

        $this->assertDatabaseHas('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track1->id,
            'position' => 1
        ]);

        $this->assertDatabaseHas('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track2->id,
            'position' => 2
        ]);
    }

    /** @test */
    public function it_can_remove_tracks_from_a_playlist()
    {
        $playlist = Playlist::factory()->create([
            'user_id' => $this->user->id
        ]);
        
        $track = Track::factory()->create([
            'genre_id' => $this->genre->id
        ]);
        
        // Add track to playlist first
        $playlist->tracks()->attach($track->id, ['position' => 1]);

        Livewire::test(Playlists::class)
            ->call('removeTrackFromPlaylist', $playlist->id, $track->id)
            ->assertEmitted('refresh-playlists');

        $this->assertDatabaseMissing('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track->id
        ]);
    }

    /** @test */
    public function it_can_update_track_positions_in_a_playlist()
    {
        $playlist = Playlist::factory()->create([
            'user_id' => $this->user->id
        ]);
        
        $track1 = Track::factory()->create(['genre_id' => $this->genre->id]);
        $track2 = Track::factory()->create(['genre_id' => $this->genre->id]);
        $track3 = Track::factory()->create(['genre_id' => $this->genre->id]);
        
        // Add tracks to playlist
        $playlist->tracks()->attach([
            $track1->id => ['position' => 1],
            $track2->id => ['position' => 2],
            $track3->id => ['position' => 3]
        ]);

        // New positions array (track3, track1, track2)
        $newPositions = [
            $track3->id => ['position' => 1],
            $track1->id => ['position' => 2],
            $track2->id => ['position' => 3]
        ];

        Livewire::test(Playlists::class)
            ->call('updateTrackPositions', $playlist->id, $newPositions)
            ->assertEmitted('refresh-playlists');

        $this->assertDatabaseHas('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track3->id,
            'position' => 1
        ]);

        $this->assertDatabaseHas('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track1->id,
            'position' => 2
        ]);

        $this->assertDatabaseHas('playlist_track', [
            'playlist_id' => $playlist->id,
            'track_id' => $track2->id,
            'position' => 3
        ]);
    }
} 