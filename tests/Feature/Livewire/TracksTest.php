<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Http\Livewire\Tracks;
use App\Models\Genre;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TracksTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Genre $genre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->genre = Genre::factory()->create(['name' => 'Test Genre']);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function the_component_can_render(): void {
        $component = Livewire::test(Tracks::class);
        $component->assertStatus(200);
    }

    /** @test */
    public function it_can_load_tracks(): void {
        $track1 = Track::factory()->create([
            'title' => 'First Track',
            'genre_id' => $this->genre->id
        ]);
        
        $track2 = Track::factory()->create([
            'title' => 'Second Track',
            'genre_id' => $this->genre->id
        ]);

        Livewire::test(Tracks::class)
            ->assertSee('First Track')
            ->assertSee('Second Track');
    }

    /** @test */
    public function it_can_search_for_tracks(): void {
        $track1 = Track::factory()->create([
            'title' => 'Rock Track',
            'genre_id' => $this->genre->id
        ]);
        
        $track2 = Track::factory()->create([
            'title' => 'Pop Track',
            'genre_id' => $this->genre->id
        ]);

        Livewire::test(Tracks::class)
            ->set('search', 'Rock')
            ->assertSee('Rock Track')
            ->assertDontSee('Pop Track');
    }

    /** @test */
    public function it_can_filter_tracks_by_genre(): void {
        $rockGenre = Genre::factory()->create(['name' => 'Rock']);
        $popGenre = Genre::factory()->create(['name' => 'Pop']);
        
        $rockTrack = Track::factory()->create([
            'title' => 'Rock Song',
            'genre_id' => $rockGenre->id
        ]);
        
        $popTrack = Track::factory()->create([
            'title' => 'Pop Song',
            'genre_id' => $popGenre->id
        ]);

        Livewire::test(Tracks::class)
            ->set('selectedGenre', $rockGenre->id)
            ->assertSee('Rock Song')
            ->assertDontSee('Pop Song');
    }

    /** @test */
    public function it_can_sort_tracks(): void {
        $trackA = Track::factory()->create([
            'title' => 'A Track',
            'genre_id' => $this->genre->id,
            'created_at' => now()->subDays(2)
        ]);
        
        $trackB = Track::factory()->create([
            'title' => 'B Track',
            'genre_id' => $this->genre->id,
            'created_at' => now()->subDay()
        ]);
        
        $trackC = Track::factory()->create([
            'title' => 'C Track',
            'genre_id' => $this->genre->id,
            'created_at' => now()
        ]);

        // Test ascending title sort
        $component = Livewire::test(Tracks::class)
            ->set('sortField', 'title')
            ->set('sortDirection', 'asc');
        
        $html = $component->payload['effects']['html'];
        $this->assertTrue(strpos($html, 'A Track') < strpos($html, 'B Track'));
        $this->assertTrue(strpos($html, 'B Track') < strpos($html, 'C Track'));

        // Test descending title sort
        $component = Livewire::test(Tracks::class)
            ->set('sortField', 'title')
            ->set('sortDirection', 'desc');
        
        $html = $component->payload['effects']['html'];
        $this->assertTrue(strpos($html, 'C Track') < strpos($html, 'B Track'));
        $this->assertTrue(strpos($html, 'B Track') < strpos($html, 'A Track'));

        // Test date sorting
        $component = Livewire::test(Tracks::class)
            ->set('sortField', 'created_at')
            ->set('sortDirection', 'desc');
        
        $html = $component->payload['effects']['html'];
        $this->assertTrue(strpos($html, 'C Track') < strpos($html, 'B Track'));
        $this->assertTrue(strpos($html, 'B Track') < strpos($html, 'A Track'));
    }

    /** @test */
    public function it_can_delete_a_track(): void {
        $track = Track::factory()->create([
            'title' => 'Track to Delete',
            'genre_id' => $this->genre->id
        ]);

        Livewire::test(Tracks::class)
            ->call('confirmDelete', $track->id)
            ->assertSet('trackIdToDelete', $track->id)
            ->assertSet('showDeleteModal', true)
            ->call('deleteTrack')
            ->assertSet('showDeleteModal', false);

        $this->assertDatabaseMissing('tracks', [
            'id' => $track->id,
        ]);
    }

    /** @test */
    public function it_can_cancel_track_deletion(): void {
        $track = Track::factory()->create([
            'title' => 'Track Not To Delete',
            'genre_id' => $this->genre->id
        ]);

        Livewire::test(Tracks::class)
            ->call('confirmDelete', $track->id)
            ->assertSet('trackIdToDelete', $track->id)
            ->assertSet('showDeleteModal', true)
            ->call('cancelDelete')
            ->assertSet('trackIdToDelete', null)
            ->assertSet('showDeleteModal', false);

        $this->assertDatabaseHas('tracks', [
            'id' => $track->id,
        ]);
    }

    /** @test */
    public function it_can_process_bulk_import(): void {
        Storage::fake('local');
        
        $csv = "title,artist,album,genre,year,duration,filename\n" .
               "Test Track 1,Test Artist 1,Test Album 1,{$this->genre->name},2023,180,test1.mp3\n" .
               "Test Track 2,Test Artist 2,Test Album 2,{$this->genre->name},2023,200,test2.mp3";
        
        $file = UploadedFile::fake()->createWithContent('tracks.csv', $csv);
        
        Livewire::test(Tracks::class)
            ->set('csvFile', $file)
            ->call('processBulkImport')
            ->assertDispatchedBrowserEvent('alert', [
                'type' => 'success',
                'message' => 'Tracks imported successfully'
            ]);
            
        $this->assertDatabaseHas('tracks', [
            'title' => 'Test Track 1',
            'artist' => 'Test Artist 1',
            'album' => 'Test Album 1',
            'year' => 2023,
            'duration' => 180,
            'filename' => 'test1.mp3',
        ]);
        
        $this->assertDatabaseHas('tracks', [
            'title' => 'Test Track 2',
            'artist' => 'Test Artist 2',
            'album' => 'Test Album 2',
            'year' => 2023,
            'duration' => 200,
            'filename' => 'test2.mp3',
        ]);
    }

    /** @test */
    public function it_validates_bulk_import_file(): void {
        // Test with invalid file type
        $file = UploadedFile::fake()->create('tracks.txt', 100);
        
        Livewire::test(Tracks::class)
            ->set('csvFile', $file)
            ->call('processBulkImport')
            ->assertHasErrors(['csvFile' => 'mimes']);
            
        // Test with no file
        Livewire::test(Tracks::class)
            ->set('csvFile', null)
            ->call('processBulkImport')
            ->assertHasErrors(['csvFile' => 'required']);
    }

    /** @test */
    public function it_can_paginate_tracks(): void {
        // Create 15 tracks (assuming per_page is 10)
        Track::factory()->count(15)->create([
            'genre_id' => $this->genre->id
        ]);

        $component = Livewire::test(Tracks::class);
        
        // Should show pagination links
        $component->assertSeeHtml('wire:click="nextPage"');
        
        // Should show the correct number of items on first page
        $this->assertEquals(10, substr_count($component->payload['effects']['html'], 'class="track-row"'));
        
        // Go to next page
        $component->call('nextPage');
        
        // Should now see 5 items
        $this->assertEquals(5, substr_count($component->payload['effects']['html'], 'class="track-row"'));
    }
} 