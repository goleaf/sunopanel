<?php

namespace Tests\Feature\Livewire;

use App\Http\Livewire\TrackEdit;
use App\Models\Genre;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TrackEditTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function the_component_can_render()
    {
        $track = Track::factory()->create();
        $response = $this->get(route('tracks.edit', $track));

        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_loads_track_data_on_mount()
    {
        $genre = Genre::factory()->create();
        $track = Track::factory()->create([
            'title' => 'Test Track Title',
            'duration' => '3:45',
            'audio_url' => 'https://example.com/test.mp3',
            'image_url' => 'https://example.com/test.jpg',
        ]);
        
        $track->genres()->attach($genre);
        
        // Note: The component may have artist and album properties that are initialized to empty string,
        // but these fields don't exist in the actual database table
        Livewire::test(TrackEdit::class, ['track' => $track])
            ->assertSet('title', 'Test Track Title')
            ->assertSet('duration', '3:45')
            ->assertSet('currentAudioUrl', 'https://example.com/test.mp3')
            ->assertSet('currentImageUrl', 'https://example.com/test.jpg')
            ->assertSet('selectedGenres', [$genre->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_required_fields()
    {
        $track = Track::factory()->create([
            'title' => 'Original Title',
        ]);
        
        Livewire::test(TrackEdit::class, ['track' => $track])
            ->set('title', '')
            ->call('save')
            ->assertHasErrors(['title']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_title_max_length()
    {
        $track = Track::factory()->create();
        $longTitle = str_repeat('a', 256); // 256 characters (over the 255 max)
        
        Livewire::test(TrackEdit::class, ['track' => $track])
            ->set('title', $longTitle)
            ->call('save')
            ->assertHasErrors(['title' => 'max']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_uploaded_files_when_provided()
    {
        $track = Track::factory()->create();
        $invalidAudioFile = UploadedFile::fake()->create('audio.zip', 100);
        $invalidImageFile = UploadedFile::fake()->create('image.txt', 100);
        
        // Test audio file validation
        Livewire::test(TrackEdit::class, ['track' => $track])
            ->set('audioFile', $invalidAudioFile)
            ->call('save')
            ->assertHasErrors(['audioFile' => 'mimes']);
            
        // Test image file validation
        Livewire::test(TrackEdit::class, ['track' => $track])
            ->set('imageFile', $invalidImageFile)
            ->call('save')
            ->assertHasErrors(['imageFile' => 'image']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_field_values()
    {
        $track = Track::factory()->create();
        
        // Instead of directly calling the 'updated' lifecycle method,
        // we'll test validation by calling the save method
        Livewire::test(TrackEdit::class, ['track' => $track])
            ->set('title', '') // Empty title
            ->call('save') 
            ->assertHasErrors(['title' => 'required']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_loads_all_genres_on_mount()
    {
        $genre1 = Genre::factory()->create(['name' => 'Rock']);
        $genre2 = Genre::factory()->create(['name' => 'Pop']);
        $track = Track::factory()->create();
        
        Livewire::test(TrackEdit::class, ['track' => $track])
            ->assertSet('allGenres', function($genres) use ($genre1, $genre2) {
                return $genres->contains($genre1) && $genres->contains($genre2);
            });
    }
} 