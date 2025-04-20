<?php

namespace App\Http\Livewire;

use App\Http\Requests\TrackStoreRequest;
use App\Models\Genre;
use App\Models\Track;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class TrackCreate extends Component
{
    use WithFileUploads;
    
    public $title = '';
    public $artist = '';
    public $album = '';
    public $duration = '';
    public $selectedGenres = [];
    public $audioFile;
    public $imageFile;
    
    protected function rules()
    {
        return (new TrackStoreRequest())->rules();
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
        
        try {
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
                $track->genres()->attach($this->selectedGenres);
            }
            
            Log::info("Track created successfully", [
                'track_id' => $track->id,
                'title' => $track->title,
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            session()->flash('success', 'Track added successfully!');
            return redirect()->route('tracks.index');
        } catch (\Exception $e) {
            Log::error("Failed to create track", [
                'error' => $e->getMessage(),
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            session()->flash('error', 'Failed to add track: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        $genres = Genre::orderBy('name')->get();
        
        return view('livewire.track-create', [
            'genres' => $genres,
        ]);
    }
} 