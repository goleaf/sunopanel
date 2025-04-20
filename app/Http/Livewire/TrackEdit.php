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

class TrackEdit extends Component
{
    use WithFileUploads;
    
    public $trackId;
    public $title = '';
    public $artist = '';
    public $album = '';
    public $duration = '';
    public $selectedGenres = [];
    public $audioFile;
    public $imageFile;
    public $currentAudioUrl;
    public $currentImageUrl;
    
    protected function rules()
    {
        return (new TrackStoreRequest())->rules();
    }
    
    protected function messages()
    {
        return (new TrackStoreRequest())->messages();
    }
    
    public function mount($id)
    {
        $this->trackId = $id;
        $track = Track::findOrFail($id);
        
        $this->title = $track->title;
        $this->artist = $track->artist;
        $this->album = $track->album;
        $this->duration = $track->duration;
        $this->selectedGenres = $track->genres->pluck('id')->toArray();
        $this->currentAudioUrl = $track->audio_url;
        $this->currentImageUrl = $track->image_url;
        
        Log::info("Track edit form accessed", [
            'track_id' => $track->id,
            'user_id' => auth()->id() ?? 'guest'
        ]);
    }
    
    public function updateTrack()
    {
        $validationRules = array_merge($this->rules(), [
            'audioFile' => 'nullable|file|mimes:mp3,wav,ogg|max:20000',
            'imageFile' => 'nullable|file|image|max:5000',
        ]);
        
        // Title doesn't need to be unique if it's the same as the current track
        $this->validate($validationRules);
        
        try {
            $track = Track::findOrFail($this->trackId);
            $track->title = $this->title;
            $track->artist = $this->artist;
            $track->album = $this->album;
            $track->duration = $this->duration;
            
            // Handle new audio file upload if provided
            if ($this->audioFile) {
                // Delete old file if exists
                if ($track->file_path && Storage::disk('public')->exists($track->file_path)) {
                    Storage::disk('public')->delete($track->file_path);
                }
                
                // Generate a safe filename for audio
                $audioOriginalName = $this->audioFile->getClientOriginalName();
                $audioExtension = $this->audioFile->getClientOriginalExtension();
                $audioBaseFileName = Str::slug(pathinfo($audioOriginalName, PATHINFO_FILENAME));
                $audioFileName = $audioBaseFileName . '.' . $audioExtension;
                
                // Store the audio file
                $audioPath = $this->audioFile->storeAs('tracks', $audioFileName, 'public');
                $track->file_path = $audioPath;
                $track->audio_url = Storage::url($audioPath);
            }
            
            // Handle new image upload if provided
            if ($this->imageFile) {
                // Delete old image if exists
                if ($track->image_url) {
                    $oldImagePath = str_replace('/storage/', '', $track->image_url);
                    if (Storage::disk('public')->exists($oldImagePath)) {
                        Storage::disk('public')->delete($oldImagePath);
                    }
                }
                
                $imageOriginalName = $this->imageFile->getClientOriginalName();
                $imageExtension = $this->imageFile->getClientOriginalExtension();
                $imageBaseFileName = Str::slug($this->title) . '-cover';
                $imageFileName = $imageBaseFileName . '.' . $imageExtension;
                
                $imagePath = $this->imageFile->storeAs('track-images', $imageFileName, 'public');
                $track->image_url = Storage::url($imagePath);
            }
            
            $track->save();
            
            // Sync genres
            $track->genres()->sync($this->selectedGenres);
            
            Log::info("Track updated successfully", [
                'track_id' => $track->id,
                'title' => $track->title,
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            session()->flash('success', 'Track updated successfully!');
            return redirect()->route('tracks.index');
        } catch (\Exception $e) {
            Log::error("Failed to update track", [
                'error' => $e->getMessage(),
                'track_id' => $this->trackId,
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            session()->flash('error', 'Failed to update track: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        $genres = Genre::orderBy('name')->get();
        
        return view('livewire.track-edit', [
            'genres' => $genres,
        ]);
    }
} 