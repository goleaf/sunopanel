<?php

namespace App\Http\Livewire;

use App\Http\Requests\TrackUpdateRequest;
use App\Livewire\BaseComponent;
use App\Models\Track;
use App\Models\Genre;
use App\Traits\WithNotifications;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;

class TrackEdit extends BaseComponent
{
    use WithFileUploads;
    use WithNotifications;
    
    /**
     * Indicates if the component should be rendered on the server.
     *
     * @var bool
     */
    protected bool $shouldRenderOnServer = true;
    
    public Track $track;
    public $title = '';
    public $artist = '';
    public $album = '';
    public $duration = '';
    public $selectedGenres = [];
    public $audioFile;
    public $imageFile;
    public $currentAudioUrl;
    public $currentImageUrl;
    public $allGenres = [];
    public $originalTitle = '';
    
    /**
     * The component's initial data for SSR.
     *
     * @return array
     */
    public function boot(): array
    {
        return [
            'placeholder' => 'Loading track editor...',
            'allGenres' => []
        ];
    }
    
    protected function rules()
    {
        // Get base rules from TrackUpdateRequest
        $baseRules = (new TrackUpdateRequest())->rules();
        
        // Only include rules for properties that exist in this component
        $componentRules = [
            'title' => 'required|string|max:255',
            'artist' => 'nullable|string|max:255',
            'album' => 'nullable|string|max:255',
            'duration' => 'nullable|string|max:10',
            'selectedGenres' => 'nullable|array',
            'selectedGenres.*' => 'exists:genres,id',
        ];
        
        // Add file validation only when files are provided
        if ($this->audioFile) {
            $componentRules['audioFile'] = 'file|mimes:mp3,wav,ogg|max:20000';
        }
        
        if ($this->imageFile) {
            $componentRules['imageFile'] = 'file|image|max:5000';
        }
        
        return $componentRules;
    }
    
    protected function messages()
    {
        return (new TrackUpdateRequest())->messages();
    }
    
    public function mount(Track $track)
    {
        $this->track = $track;
        $this->title = $track->title;
        $this->originalTitle = $track->title;
        $this->artist = $track->artist;
        $this->album = $track->album;
        $this->duration = $track->duration;
        $this->selectedGenres = $track->genres->pluck('id')->toArray();
        $this->currentAudioUrl = $track->audio_url;
        $this->currentImageUrl = $track->image_url;
        $this->allGenres = Genre::orderBy('name')->get();
    }
    
    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }
    
    public function save()
    {
        $this->validate();

        DB::transaction(function () {
            $this->track->update([
                'title' => $this->title,
                'artist' => $this->artist,
                'album' => $this->album,
                'duration' => $this->duration,
            ]);
            
            // Handle new audio file upload if provided
            if ($this->audioFile) {
                // Delete old file if exists
                if ($this->track->file_path && Storage::disk('public')->exists($this->track->file_path)) {
                    Storage::disk('public')->delete($this->track->file_path);
                }
                
                // Generate a safe filename for audio
                $audioOriginalName = $this->audioFile->getClientOriginalName();
                $audioExtension = $this->audioFile->getClientOriginalExtension();
                $audioBaseFileName = Str::slug(pathinfo($audioOriginalName, PATHINFO_FILENAME));
                $audioFileName = $audioBaseFileName . '.' . $audioExtension;
                
                // Store the audio file
                $audioPath = $this->audioFile->storeAs('tracks', $audioFileName, 'public');
                $this->track->file_path = $audioPath;
                $this->track->audio_url = Storage::url($audioPath);
            }
            
            // Handle new image upload if provided
            if ($this->imageFile) {
                // Delete old image if exists
                if ($this->track->image_url) {
                    $oldImagePath = str_replace('/storage/', '', $this->track->image_url);
                    if (Storage::disk('public')->exists($oldImagePath)) {
                        Storage::disk('public')->delete($oldImagePath);
                    }
                }
                
                $imageOriginalName = $this->imageFile->getClientOriginalName();
                $imageExtension = $this->imageFile->getClientOriginalExtension();
                $imageBaseFileName = Str::slug($this->title) . '-cover';
                $imageFileName = $imageBaseFileName . '.' . $imageExtension;
                
                $imagePath = $this->imageFile->storeAs('track-images', $imageFileName, 'public');
                $this->track->image_url = Storage::url($imagePath);
            }
            
            $this->track->save();
            
            // Sync genres
            if (!empty($this->selectedGenres)) {
                $this->track->genres()->sync($this->selectedGenres);
            } else {
                // If both are empty, detach all genres
                $this->track->genres()->detach();
            }
        });
        
        $this->notifySuccess('Track updated successfully!');
        return redirect()->route('tracks.index');
    }
    
    /**
     * Update an existing track from validated data.
     */
    protected function updateTrack(array $validated, Track $track): Track
    {
        // Update track fields
        $track->update([
            'title' => $validated['title'],
            'audio_url' => $validated['audio_url'] ?? $track->audio_url,
            'image_url' => $validated['image_url'] ?? $track->image_url,
            'duration' => $validated['duration'] ?? $track->duration,
            'artist' => $validated['artist'] ?? $track->artist,
            'album' => $validated['album'] ?? $track->album,
        ]);

        // Sync genres if present in the validated data
        if (array_key_exists('genre_ids', $validated)) {
            $track->genres()->sync(Arr::wrap($validated['genre_ids'] ?? []));
        }

        // Sync playlists if present in the validated data
        if (array_key_exists('playlists', $validated)) {
            $track->playlists()->sync(Arr::wrap($validated['playlists'] ?? []));
        }

        return $track->fresh(['genres', 'playlists']);
    }
    
    /**
     * Render the component
     */
    #[Title('Edit Track')]
    public function render()
    {
        return $this->renderWithServerRendering(view('livewire.track-edit', [
            'track' => $this->track,
            'genres' => $this->allGenres,
        ]));
    }
} 