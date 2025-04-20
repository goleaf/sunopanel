<?php

namespace App\Http\Livewire;

use App\Models\Genre;
use App\Models\Track;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class Tracks extends Component
{
    use WithPagination;
    
    public $search = '';
    public $genreFilter = '';
    public $perPage = 10;
    public $sortField = 'created_at';
    public $direction = 'desc';
    
    public $showDeleteModal = false;
    public $trackIdToDelete = null;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'genreFilter' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortField' => ['except' => 'created_at'],
        'direction' => ['except' => 'desc'],
    ];
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingGenreFilter()
    {
        $this->resetPage();
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->direction = 'asc';
        }
    }
    
    public function confirmDelete($trackId)
    {
        $this->trackIdToDelete = $trackId;
        $this->showDeleteModal = true;
    }
    
    public function cancelDelete()
    {
        $this->trackIdToDelete = null;
        $this->showDeleteModal = false;
    }
    
    public function deleteTrack()
    {
        if (!$this->trackIdToDelete) {
            return;
        }
        
        try {
            $track = Track::findOrFail($this->trackIdToDelete);
            
            // Delete file from storage if it exists
            if ($track->file_path && Storage::disk('public')->exists($track->file_path)) {
                Storage::disk('public')->delete($track->file_path);
            }
            
            // Delete track
            $track->delete();
            
            Log::info("Track deleted successfully", [
                'track_id' => $this->trackIdToDelete,
                'track_title' => $track->title,
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            session()->flash('success', 'Track deleted successfully.');
        } catch (\Exception $e) {
            Log::error("Failed to delete track", [
                'track_id' => $this->trackIdToDelete,
                'error' => $e->getMessage(),
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            session()->flash('error', 'Failed to delete track: ' . $e->getMessage());
        }
        
        $this->trackIdToDelete = null;
        $this->showDeleteModal = false;
    }
    
    public function render()
    {
        $tracks = Track::query()
            ->with(['genres'])
            ->when($this->search, function ($query) {
                return $query->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('artist', 'like', '%' . $this->search . '%')
                      ->orWhere('album', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->genreFilter, function ($query) {
                return $query->whereHas('genres', function ($q) {
                    $q->where('genres.id', $this->genreFilter);
                });
            })
            ->orderBy($this->sortField, $this->direction)
            ->paginate($this->perPage);
        
        $genres = Genre::orderBy('name')->get();
        
        return view('livewire.tracks', [
            'tracks' => $tracks,
            'genres' => $genres,
        ]);
    }
} 