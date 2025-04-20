<?php

namespace App\Http\Livewire;

use App\Http\Requests\BulkTrackRequest;
use App\Models\Genre;
use App\Models\Track;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class TrackUpload extends Component
{
    use WithFileUploads;
    
    public $files = [];
    public $defaultGenreId;
    public $processedCount = 0;
    public $failedCount = 0;
    public $uploadComplete = false;
    public $genres;
    
    protected function rules()
    {
        return (new BulkTrackRequest())->rules();
    }
    
    protected function messages()
    {
        return (new BulkTrackRequest())->messages();
    }
    
    public function mount()
    {
        $this->genres = Genre::orderBy('name')->get();
    }
    
    public function updatedFiles()
    {
        $this->validate([
            'files.*' => 'file|mimes:mp3,wav,ogg|max:20000',
        ]);
    }
    
    public function processBulkUpload()
    {
        $this->validate();
        
        $this->processedCount = 0;
        $this->failedCount = 0;
        
        foreach ($this->files as $file) {
            // Generate a safe filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $baseFileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
            $fileName = $baseFileName . '.' . $extension;
            
            // Store the file and get the path
            $path = $file->storeAs('tracks', $fileName, 'public');
            
            // Create track record
            $track = new Track();
            $track->title = pathinfo($originalName, PATHINFO_FILENAME);
            $track->file_path = $path;
            $track->audio_url = Storage::url($path);
            
            if ($this->defaultGenreId) {
                $genre = Genre::find($this->defaultGenreId);
                if ($genre) {
                    $track->genre_id = $genre->id;
                }
            }
            
            $track->save();
            
            if ($this->defaultGenreId) {
                $track->genres()->attach($this->defaultGenreId);
            }
            
            $this->processedCount++;
            
            Log::info("Track uploaded successfully", [
                'track_id' => $track->id,
                'file_name' => $fileName,
                'user_id' => auth()->id() ?? 'guest'
            ]);
        }
        
        $this->uploadComplete = true;
        $this->files = [];
        
        session()->flash('success', "$this->processedCount tracks uploaded successfully. $this->failedCount failed.");
    }
    
    public function render()
    {
        return view('livewire.track-upload', [
            'genres' => $this->genres,
        ]);
    }
} 