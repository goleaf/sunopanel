<?php

namespace App\Http\Livewire;

use App\Http\Requests\TrackStoreRequest;
use App\Models\Genre;
use App\Models\Track;
use App\Traits\WithNotifications;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Livewire\Attributes\Title;
use Throwable;
use App\Livewire\BaseComponent;

class TrackCreate extends BaseComponent
{
    use WithFileUploads;
    
    /**
     * Indicates if the component should be rendered on the server.
     *
     * @var bool
     */
    protected bool $shouldRenderOnServer = true;
    
    public $title = '';
    public $artist = '';
    public $album = '';
    public $duration = '';
    public $selectedGenres = [];
    public $audioFile;
    public $imageFile;
    
    /**
     * The component's initial data for SSR.
     *
     * @return array
     */
    public function boot(): array
    {
        return [
            'placeholder' => 'Loading track creation form...'
        ];
    }
    
    protected function rules()
    {
        $trackRequestRules = (new TrackStoreRequest())->rules();
        
        // Only include rules for properties that exist in this component
        $componentRules = [
            'title' => $trackRequestRules['title'],
            'artist' => $trackRequestRules['artist'],
            'album' => $trackRequestRules['album'],
            'duration' => $trackRequestRules['duration'],
            'selectedGenres' => 'nullable|array',
            'selectedGenres.*' => 'exists:genres,id',
            'audioFile' => 'required|file|mimes:mp3,wav,ogg|max:20000',
            'imageFile' => 'nullable|file|image|max:5000',
        ];
        
        return $componentRules;
    }
    
    protected function messages()
    {
        return (new TrackStoreRequest())->messages();
    }
    
    public function mount()
    {
        // Initialize to empty state
    }
    
    public function saveTrack()
    {
        // Map the component properties to match the request validation
        $this->validate(array_merge($this->rules(), [
            'audioFile' => 'required|file|mimes:mp3,wav,ogg|max:20000',
            'imageFile' => 'nullable|file|image|max:5000',
        ]));
        
        DB::transaction(function () {
            // Generate a safe filename for audio
            $audioOriginalName = $this->audioFile->getClientOriginalName();
            $audioExtension = $this->audioFile->getClientOriginalExtension();
            $audioBaseFileName = Str::slug(pathinfo($audioOriginalName, PATHINFO_FILENAME));
            $audioFileName = $audioBaseFileName . '.' . $audioExtension;
            
            // Store the audio file
            $audioPath = $this->audioFile->storeAs('tracks', $audioFileName, 'public');
            
            // Create track record
            $track = new Track();
            $track->title = $this->title;
            $track->artist = $this->artist;
            $track->album = $this->album;
            $track->duration = $this->duration;
            $track->file_path = $audioPath;
            $track->audio_url = Storage::url($audioPath);
            $track->unique_id = Track::generateUniqueId($this->title);
            
            // Handle image upload if provided
            if ($this->imageFile) {
                $imageOriginalName = $this->imageFile->getClientOriginalName();
                $imageExtension = $this->imageFile->getClientOriginalExtension();
                $imageBaseFileName = Str::slug($this->title) . '-cover';
                $imageFileName = $imageBaseFileName . '.' . $imageExtension;
                
                $imagePath = $this->imageFile->storeAs('track-images', $imageFileName, 'public');
                $track->image_url = Storage::url($imagePath);
            }
            
            $track->save();
            
            // Attach genres if selected
            if (!empty($this->selectedGenres)) {
                $track->genres()->sync($this->selectedGenres);
            }
            
            Log::info("Track created successfully", [
                'track_id' => $track->id,
                'title' => $track->title,
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            session()->flash('success', 'Track added successfully!');
        });
        
        return redirect()->route('tracks.index');
    }
    
    /**
     * Store a new track from validated data.
     */
    protected function storeTrack(array $validated): Track
    {
        // Create track
        $track = Track::create([
            'title' => $validated['title'],
            'audio_url' => $validated['audio_url'],
            'image_url' => $validated['image_url'] ?? null,
            'duration' => $validated['duration'] ?? null,
            'artist' => $validated['artist'] ?? null,
            'album' => $validated['album'] ?? null,
            'unique_id' => Track::generateUniqueId($validated['title']),
        ]);

        // Sync genres
        if (isset($validated['genre_ids'])) {
            $track->genres()->sync(Arr::wrap($validated['genre_ids']));
        }

        // Attach playlists
        if (isset($validated['playlists'])) {
            $track->playlists()->attach(Arr::wrap($validated['playlists']));
        }

        return $track;
    }
    
    public function save()
    {
        return $this->saveTrack();
    }
    
    /**
     * Render the component
     */
    #[Title('Create New Track')]
    public function render()
    {
        $genres = Genre::orderBy('name')->get();
        
        return $this->renderWithServerRendering(view('livewire.track-create', [
            'genres' => $genres,
        ]));
    }
} 