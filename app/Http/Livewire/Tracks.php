<?php

namespace App\Http\Livewire;

use App\Http\Requests\BulkTrackRequest;
use App\Http\Requests\TrackListRequest;
use App\Http\Requests\TrackStoreRequest;
use App\Http\Requests\TrackUpdateRequest;
use App\Models\Genre;
use App\Models\Track;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

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
    
    protected function rules()
    {
        return (new TrackListRequest())->rules();
    }
    
    protected function messages()
    {
        return (new TrackListRequest())->messages();
    }
    
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
        $this->validate(['trackIdToDelete' => 'exists:tracks,id'], 
            [], ['trackIdToDelete' => $trackId]);
        
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
        
        $this->validate(['trackIdToDelete' => 'exists:tracks,id']);
        
        $track = Track::findOrFail($this->trackIdToDelete);
        
        DB::transaction(function () use ($track) {
            // Detach from genres and playlists
            $track->genres()->detach();
            $track->playlists()->detach();
            
            // Delete file from storage if it exists
            if ($track->file_path && Storage::disk('public')->exists($track->file_path)) {
                Storage::disk('public')->delete($track->file_path);
            }
            
            // Delete track
            $track->delete();
        });
        
        Log::info("Track deleted successfully", [
            'track_id' => $this->trackIdToDelete,
            'track_title' => $track->title,
            'user_id' => auth()->id() ?? 'guest'
        ]);
        
        session()->flash('success', 'Track deleted successfully.');
        
        $this->trackIdToDelete = null;
        $this->showDeleteModal = false;
    }
    
    /**
     * Process bulk track upload from text data.
     * Returns [processedCount, errors[]]
     */
    public function processBulkImport(string $bulkTracksData): array
    {
        $lines = explode(PHP_EOL, trim($bulkTracksData));
        $processedCount = 0;
        $errors = [];

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $lineNumber = $index + 1;

            // Split only by pipe character
            $parts = explode('|', $line);
            if (count($parts) < 4) {
                $errors[] = "Line {$lineNumber}: Invalid format - expected at least 4 parts separated by |";
                continue;
            }

            // Sanitize all input data
            $title = htmlspecialchars(trim($parts[0]), ENT_QUOTES, 'UTF-8');
            $audioUrl = filter_var(trim($parts[1]), FILTER_SANITIZE_URL);
            $imageUrl = filter_var(trim($parts[2]), FILTER_SANITIZE_URL);
            $genresRaw = htmlspecialchars(trim($parts[3]), ENT_QUOTES, 'UTF-8');
            $duration = isset($parts[4]) ? htmlspecialchars(trim($parts[4]), ENT_QUOTES, 'UTF-8') : '3:00'; // Default duration

            // Basic Validation
            if (empty($title)) {
                 $errors[] = "Line {$lineNumber}: Title cannot be empty.";
                 continue;
            }

            if (empty($audioUrl)) {
                 $errors[] = "Line {$lineNumber}: Audio URL cannot be empty.";
                 continue;
            }

            // Check for existing track with the same title (optional, can allow duplicates)
            if (Track::where('title', $title)->exists()) {
                // Skip existing tracks to prevent duplicates
                continue;
            }

            DB::transaction(function () use ($title, $audioUrl, $imageUrl, $duration, $genresRaw, &$processedCount) {
                // Create the track
                $track = Track::create([
                    'title' => $title,
                    'audio_url' => $audioUrl,
                    'image_url' => $imageUrl,
                    'duration' => $duration,
                    'unique_id' => Track::generateUniqueId($title),
                ]);

                // Handle genres
                $this->handleGenres($track, $genresRaw);

                $processedCount++;
            });
        }

        return [$processedCount, $errors];
    }

    /**
     * Handle track genres on import
     */
    private function handleGenres(Track $track, string $genresRaw): void
    {
        if (empty($genresRaw)) {
            return;
        }

        // Get all existing genres
        $existingGenres = Genre::all();
        
        // Split and trim genre names
        $genreNames = array_map('trim', explode(',', $genresRaw));

        $genreIds = [];
        foreach ($genreNames as $genreName) {
            if (empty($genreName)) continue;

            // Find or create each genre
            $genre = $existingGenres->firstWhere('name', $genreName);
            if (!$genre) {
                $genre = Genre::create(['name' => $genreName]);
            }

            $genreIds[] = $genre->id;
        }

        // Sync genres to the track
        if (!empty($genreIds)) {
            $track->genres()->sync($genreIds);
        }
    }
    
    /**
     * Store a new track from validated data.
     */
    public function storeTrack(array $validated): Track
    {
        // Create track
        $track = Track::create([
            'title' => $validated['title'],
            'audio_url' => $validated['audio_url'],
            'image_url' => $validated['image_url'] ?? null,
            'duration' => $validated['duration'] ?? null,
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

    /**
     * Update an existing track from validated data.
     */
    public function updateTrack(array $validated, Track $track): Track
    {
        // Update track fields
        $track->update([
            'title' => $validated['title'],
            'audio_url' => $validated['audio_url'],
            'image_url' => $validated['image_url'] ?? $track->image_url,
            'duration' => $validated['duration'] ?? $track->duration,
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
     * Get popular tracks
     */
    public function getPopularTracks(int $limit = 10)
    {
        return Track::orderBy('play_count', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Increment play count for a track
     */
    public function incrementPlayCount(Track $track): void
    {
        $track->increment('play_count');
    }
    
    /**
     * Get all tracks
     */
    public function getAllTracks()
    {
        return Track::all();
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