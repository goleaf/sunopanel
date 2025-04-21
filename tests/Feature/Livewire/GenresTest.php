<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Http\Livewire\Genres;
use App\Models\Genre;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class GenresTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Auth::login($this->user);
    }

    /** @test */
    public function the_component_can_render()
    {
        $component = Livewire::test(Genres::class);
        $component->assertStatus(200);
    }

    /** @test */
    public function it_can_load_genres()
    {
        $genre1 = Genre::factory()->create(['name' => 'Rock']);
        $genre2 = Genre::factory()->create(['name' => 'Pop']);

        Livewire::test(Genres::class)
            ->assertSee('Rock')
            ->assertSee('Pop');
    }

    /** @test */
    public function it_can_search_for_genres()
    {
        $genre1 = Genre::factory()->create(['name' => 'Rock']);
        $genre2 = Genre::factory()->create(['name' => 'Pop']);
        $genre3 = Genre::factory()->create(['name' => 'Rock & Roll']);

        Livewire::test(Genres::class)
            ->set('search', 'Rock')
            ->assertSee('Rock')
            ->assertSee('Rock & Roll')
            ->assertDontSee('Pop');
    }

    /** @test */
    public function it_can_sort_genres()
    {
        $genreA = Genre::factory()->create([
            'name' => 'A Genre',
            'created_at' => now()->subDays(2)
        ]);
        
        $genreB = Genre::factory()->create([
            'name' => 'B Genre',
            'created_at' => now()->subDay()
        ]);
        
        $genreC = Genre::factory()->create([
            'name' => 'C Genre',
            'created_at' => now()
        ]);

        // Test ascending name sort
        $component = Livewire::test(Genres::class)
            ->set('sortField', 'name')
            ->set('sortDirection', 'asc');
        
        $html = $component->payload['effects']['html'];
        $this->assertTrue(strpos($html, 'A Genre') < strpos($html, 'B Genre'));
        $this->assertTrue(strpos($html, 'B Genre') < strpos($html, 'C Genre'));

        // Test descending name sort
        $component = Livewire::test(Genres::class)
            ->set('sortField', 'name')
            ->set('sortDirection', 'desc');
        
        $html = $component->payload['effects']['html'];
        $this->assertTrue(strpos($html, 'C Genre') < strpos($html, 'B Genre'));
        $this->assertTrue(strpos($html, 'B Genre') < strpos($html, 'A Genre'));

        // Test creation date sorting
        $component = Livewire::test(Genres::class)
            ->set('sortField', 'created_at')
            ->set('sortDirection', 'desc');
        
        $html = $component->payload['effects']['html'];
        $this->assertTrue(strpos($html, 'C Genre') < strpos($html, 'B Genre'));
        $this->assertTrue(strpos($html, 'B Genre') < strpos($html, 'A Genre'));
    }

    /** @test */
    public function it_can_paginate_genres()
    {
        // Create 15 genres (assuming per_page is 10)
        Genre::factory()->count(15)->create();

        $component = Livewire::test(Genres::class);
        
        // Should show pagination links
        $component->assertSeeHtml('wire:click="nextPage"');
        
        // Should show the correct number of items on first page
        $this->assertEquals(10, substr_count($component->payload['effects']['html'], 'class="genre-row"'));
        
        // Go to next page
        $component->call('nextPage');
        
        // Should now see 5 items
        $this->assertEquals(5, substr_count($component->payload['effects']['html'], 'class="genre-row"'));
    }

    /** @test */
    public function it_can_create_a_new_genre()
    {
        Livewire::test(Genres::class)
            ->set('name', 'New Test Genre')
            ->set('description', 'This is a test genre')
            ->call('store')
            ->assertEmitted('refreshGenres');

        $this->assertDatabaseHas('genres', [
            'name' => 'New Test Genre',
            'description' => 'This is a test genre'
        ]);
    }

    /** @test */
    public function it_validates_genre_name_uniqueness()
    {
        Genre::factory()->create(['name' => 'Existing Genre']);

        Livewire::test(Genres::class)
            ->set('name', 'Existing Genre')
            ->set('description', 'This is a test genre')
            ->call('store')
            ->assertHasErrors(['name' => 'unique']);
    }

    /** @test */
    public function it_can_update_a_genre()
    {
        $genre = Genre::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original description'
        ]);

        Livewire::test(Genres::class)
            ->call('edit', $genre->id)
            ->assertSet('genreId', $genre->id)
            ->assertSet('name', 'Original Name')
            ->assertSet('description', 'Original description')
            ->set('name', 'Updated Name')
            ->set('description', 'Updated description')
            ->call('update')
            ->assertEmitted('refreshGenres');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'Updated Name',
            'description' => 'Updated description'
        ]);
    }

    /** @test */
    public function it_can_delete_a_genre()
    {
        $genre = Genre::factory()->create(['name' => 'Genre to Delete']);

        Livewire::test(Genres::class)
            ->call('confirmDelete', $genre->id)
            ->assertSet('genreIdToDelete', $genre->id)
            ->assertSet('showDeleteModal', true)
            ->call('delete')
            ->assertSet('showDeleteModal', false);

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    /** @test */
    public function it_can_cancel_genre_deletion()
    {
        $genre = Genre::factory()->create(['name' => 'Genre Not To Delete']);

        Livewire::test(Genres::class)
            ->call('confirmDelete', $genre->id)
            ->assertSet('genreIdToDelete', $genre->id)
            ->assertSet('showDeleteModal', true)
            ->call('cancelDelete')
            ->assertSet('genreIdToDelete', null)
            ->assertSet('showDeleteModal', false);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }

    /** @test */
    public function it_cannot_delete_genre_with_tracks()
    {
        $genre = Genre::factory()->create(['name' => 'Genre With Tracks']);
        $track = Track::factory()->create(['genre_id' => $genre->id]);

        Livewire::test(Genres::class)
            ->call('confirmDelete', $genre->id)
            ->call('delete')
            ->assertDispatchedBrowserEvent('alert', [
                'type' => 'error'
            ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }
} 