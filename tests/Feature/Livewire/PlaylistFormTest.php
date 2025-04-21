<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Http\Livewire\PlaylistForm;
use App\Models\Genre;
use App\Models\Playlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class PlaylistFormTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    #[\PHPUnit\Framework\Attributes\Test]
    public function the_create_component_can_render(): void {
        $response = $this->get(route('playlists.create'));

        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function the_edit_component_can_render(): void {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
        ]);

        $response = $this->get(route('playlists.edit', $playlist));

        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_loads_playlist_data_when_editing(): void {
        $genre = Genre::factory()->create();
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
            'title' => 'Test Playlist',
            'description' => 'Test Description',
            'cover_image' => 'https://example.com/image.jpg',
            'is_public' => true,
        ]);

        Livewire::test(PlaylistForm::class, ['playlist' => $playlist])
            ->assertSet('playlistId', $playlist->id)
            ->assertSet('title', 'Test Playlist')
            ->assertSet('description', 'Test Description')
            ->assertSet('genre_id', $genre->id)
            ->assertSet('cover_image', 'https://example.com/image.jpg')
            ->assertSet('is_public', true)
            ->assertSet('isEditing', true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_loads_genres_on_mount(): void {
        $genre1 = Genre::factory()->create(['name' => 'Rock']);
        $genre2 = Genre::factory()->create(['name' => 'Pop']);
        
        $component = Livewire::test(PlaylistForm::class)
            ->assertSeeHtml('Rock')
            ->assertSeeHtml('Pop');
            
        // We're testing that the component loads and displays genres
        // without relying on LoggingService
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_store_a_new_playlist(): void {
        $genre = Genre::factory()->create();
        
        Livewire::test(PlaylistForm::class)
            ->set('title', 'New Test Playlist')
            ->set('description', 'A new playlist description')
            ->set('genre_id', $genre->id)
            ->set('cover_image', 'https://example.com/cover.jpg')
            ->set('is_public', true)
            ->call('store')
            ->assertRedirect(route('playlists.add-tracks', 1)); // ID will be 1 in a fresh DB
            
        $this->assertDatabaseHas('playlists', [
            'title' => 'New Test Playlist',
            'description' => 'A new playlist description',
            'genre_id' => $genre->id,
            'cover_image' => 'https://example.com/cover.jpg',
            'is_public' => true,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_update_an_existing_playlist(): void {
        $genre = Genre::factory()->create();
        $newGenre = Genre::factory()->create();
        
        $playlist = Playlist::factory()->create([
            'genre_id' => $genre->id,
            'title' => 'Original Title',
            'description' => 'Original Description',
        ]);
        
        Livewire::test(PlaylistForm::class, ['playlist' => $playlist])
            ->set('title', 'Updated Title')
            ->set('description', 'Updated Description')
            ->set('genre_id', $newGenre->id)
            ->set('is_public', false)
            ->call('update')
            ->assertRedirect(route('playlists.show', $playlist));
            
        $this->assertDatabaseHas('playlists', [
            'id' => $playlist->id,
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'genre_id' => $newGenre->id,
            'is_public' => false,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function title_is_required(): void {
        $genre = Genre::factory()->create();
        
        Livewire::test(PlaylistForm::class)
            ->set('title', '')
            ->set('description', 'A description')
            ->set('genre_id', $genre->id)
            ->call('store')
            ->assertHasErrors(['title' => 'required']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function genre_id_must_exist_in_genres_table(): void {
        Livewire::test(PlaylistForm::class)
            ->set('title', 'Test Playlist')
            ->set('description', 'A description')
            ->set('genre_id', 999) // Non-existent genre ID
            ->call('store')
            ->assertHasErrors(['genre_id' => 'exists']);
    }
} 