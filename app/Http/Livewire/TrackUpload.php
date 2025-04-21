<?php

namespace App\Http\Livewire;

use App\Http\Requests\BulkTrackRequest;
use App\Livewire\BaseComponent;
use App\Models\Genre;
use App\Models\Track;
use App\Traits\WithNotifications;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Livewire\Attributes\Title;

class TrackUpload extends BaseComponent
{
    use WithFileUploads;
    use WithNotifications;
    
    /**
     * Indicates if the component should be rendered on the server.
     *
     * @var bool
     */
    protected bool $shouldRenderOnServer = true;
    
    public $files = [];
    public $defaultGenreId;
    public $processedCount = 0;
    public $failedCount = 0;
    public $uploadComplete = false;
    public $genres;
    
    /**
     * The component's initial data for SSR.
     *
     * @return array
     */
    public function boot(): array
    {
        return [
            'placeholder' => 'Loading bulk upload form...',
            'genres' => []
        ];
    }
    
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
        
        $this->notifySuccess("$this->processedCount tracks uploaded successfully. $this->failedCount failed.");
    }
    
    /**
     * Render the component
     */
    #[Title('Upload Tracks')]
    public function render()
    {
        return $this->renderWithServerRendering(view('livewire.track-upload', [
            'genres' => $this->genres,
        ]));
    }
} 