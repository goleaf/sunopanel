<?php

namespace App\Http\Livewire;

use App\Http\Requests\GenreStoreRequest;
use App\Http\Requests\GenreUpdateRequest;
use App\Http\Requests\PlaylistCreateFromGenreRequest;
use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use App\Traits\WithNotifications;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class Genres extends Component
{
    use WithPagination;
    use WithNotifications;
    
    public $name = '';
    public $description = '';
    public $editingGenreId = null;
    public $search = '';
    public $perPage = 15;
    public $sortField = 'name';
    public $direction = 'asc';
    public $showDeleteModal = false;
    public $genreIdToDelete = null;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'name'],
        'direction' => ['except' => 'asc'],
        'perPage' => ['except' => 15],
    ];
    
    protected function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:genres,name,' . ($this->editingGenreId ?? '')],
            'description' => ['nullable', 'string'],
        ];
    }

    public function mount()
    {
        // Empty mount method - pagination and data fetching happens in render
    }

    /**
     * Refresh the genres list
     * @deprecated Use resetPage directly
     */
    public function loadGenres()
    {
        $this->resetPage();
    }
    
    /**
     * Get paginated genres with filtering and sorting
     * @deprecated Use render method directly
     * @private
     */
    private function getPaginatedGenres()
    {
        $query = Genre::query()->withCount('tracks');

        // Handle search
        if (!empty($this->search)) {
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        // Handle sorting
        $allowedSortFields = ['name', 'created_at', 'tracks_count'];
        $sortField = in_array($this->sortField, $allowedSortFields) ? $this->sortField : 'name';
        $direction = in_array($this->direction, ['asc', 'desc']) ? $this->direction : 'asc';

        $query->orderBy($sortField, $direction);
        
        return $query->paginate($this->perPage);
    }
    
    /**
     * Get all genres
     */
    public function getAllGenres(): Collection
    {
        return Genre::orderBy('name')->get();
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
    
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $validatedData = $this->validate();
        
        try {
            $genre = $this->storeFromArray($validatedData);
            
            $this->resetInputFields();
            $this->notifySuccess('Genre created successfully!');
            $this->resetPage();
        } catch (Throwable $e) {
            Log::error('Error creating genre', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id() ?? 'guest'
            ]);
            
            $this->notifyError('Error creating genre: ' . $e->getMessage());
        }
    }
    
    /**
     * Store a new genre from array data
     */
    public function storeFromArray(array $validatedData): Genre
    {
        // Generate a slug from the name if not provided
        if (! isset($validatedData['slug'])) {
            $validatedData['slug'] = Str::slug($validatedData['name']);
        }

        $genre = Genre::create([
            'name' => $validatedData['name'],
            'slug' => $validatedData['slug'],
            'description' => $validatedData['description'] ?? null,
        ]);

        Log::info('Genre created successfully', [
            'genre_id' => $genre->id,
            'name' => $genre->name,
            'slug' => $genre->slug,
        ]);

        return $genre;
    }

    public function edit($id)
    {
        $genre = Genre::findOrFail($id);
        $this->editingGenreId = $id;
        $this->name = $genre->name;
        $this->description = $genre->description;
    }

    public function update()
    {
        $validatedData = $this->validate();
        
        try {
            $genre = Genre::findOrFail($this->editingGenreId);
            $genre = $this->updateFromArray($validatedData, $genre);
            
            $this->resetInputFields();
            $this->notifySuccess('Genre updated successfully!');
            $this->resetPage();
        } catch (Throwable $e) {
            Log::error('Error updating genre', [
                'genre_id' => $this->editingGenreId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id() ?? 'guest'
            ]);
            
            $this->notifyError('Error updating genre: ' . $e->getMessage());
        }
    }
    
    /**
     * Update an existing genre from array data
     */
    public function updateFromArray(array $validatedData, Genre $genre): Genre
    {
        // Update slug if name changes and slug is not explicitly provided
        if (isset($validatedData['name']) && $validatedData['name'] !== $genre->name && ! isset($validatedData['slug'])) {
            $validatedData['slug'] = Str::slug($validatedData['name']);
        }

        $genre->update([
            'name' => $validatedData['name'] ?? $genre->name,
            'slug' => $validatedData['slug'] ?? $genre->slug,
            'description' => $validatedData['description'] ?? $genre->description,
        ]);

        Log::info('Genre updated successfully', [
            'genre_id' => $genre->id,
            'name' => $genre->name,
            'slug' => $genre->slug,
        ]);

        return $genre;
    }
    
    public function confirmDelete($id)
    {
        $this->genreIdToDelete = $id;
        $this->showDeleteModal = true;
    }
    
    public function cancelDelete()
    {
        $this->genreIdToDelete = null;
        $this->showDeleteModal = false;
    }

    public function delete()
    {
        if (!$this->genreIdToDelete) {
            return;
        }
        
        try {
            $genre = Genre::findOrFail($this->genreIdToDelete);
            
            // Check if genre has associated tracks
            $tracksCount = $genre->tracks()->count();
            
            if ($tracksCount > 0) {
                Log::warning('Cannot delete genre with associated tracks', [
                    'genre_id' => $genre->id,
                    'name' => $genre->name,
                    'tracks_count' => $tracksCount,
                ]);
                
                $this->notifyError('Cannot delete genre with associated tracks. Remove the tracks first or reassign them to another genre.');
                $this->showDeleteModal = false;
                return;
            }
            
            $deleted = $this->deleteGenre($genre);
            
            if ($deleted) {
                $this->notifySuccess('Genre deleted successfully!');
                $this->resetPage();
            } else {
                $this->notifyError('Error deleting genre. Please try again.');
            }
            
            $this->showDeleteModal = false;
            $this->genreIdToDelete = null;
        } catch (Throwable $e) {
            Log::error('Error deleting genre', [
                'genre_id' => $this->genreIdToDelete,
                'error' => $e->getMessage(),
                'user_id' => Auth::id() ?? 'guest'
            ]);
            
            $this->notifyError('Error deleting genre: ' . $e->getMessage());
            $this->showDeleteModal = false;
            $this->genreIdToDelete = null;
        }
    }
    
    /**
     * Delete a genre
     */
    public function deleteGenre(Genre $genre): bool
    {
        // Check if genre has associated tracks
        $tracksCount = $genre->tracks()->count();

        if ($tracksCount > 0) {
            Log::warning('Cannot delete genre with associated tracks', [
                'genre_id' => $genre->id,
                'name' => $genre->name,
                'tracks_count' => $tracksCount,
            ]);

            return false;
        }

        $deleted = $genre->delete();

        Log::info('Genre deleted', [
            'genre_id' => $genre->id,
            'name' => $genre->name,
            'success' => $deleted,
        ]);

        return $deleted;
    }
    
    /**
     * Delete a genre and detach tracks
     */
    public function deleteGenreAndDetachTracks(Genre $genre): bool
    {
        try {
            return DB::transaction(function () use ($genre) {
                // Detach from pivot tables
                $genre->tracks()->detach();
                
                // Remove genre_id from playlists that use this genre
                Playlist::where('genre_id', $genre->id)->update(['genre_id' => null]);
                
                // Delete the genre
                $deleted = $genre->delete();
                
                Log::info('Genre deleted with detached tracks', [
                    'genre_id' => $genre->id,
                    'name' => $genre->name,
                    'success' => $deleted,
                ]);
                
                return (bool) $deleted;
            });
        } catch (Throwable $e) {
            Log::error('Error deleting genre and detaching tracks', [
                'genre_id' => $genre->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Get paginated tracks for a genre
     */
    public function getPaginatedTracksForGenre(Genre $genre, Request $request): LengthAwarePaginator
    {
        $query = $genre->tracks()->with('genres');
        
        // Handle search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', '%'.$search.'%');
        }
        
        // Handle sorting
        $sortField = $request->input('sort', 'title');
        $direction = $request->input('direction', 'asc');
        
        // Validate sort field
        $allowedSortFields = ['title', 'created_at', 'duration'];
        $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'title';
        $direction = in_array($direction, ['asc', 'desc']) ? $direction : 'asc';
        
        $query->orderBy($sortField, $direction);
        
        // Paginate
        $perPage = (int) $request->input('per_page', 15);
        
        return $query->paginate($perPage)->withQueryString();
    }
    
    /**
     * Get a genre with its tracks
     */
    public function getWithTracks(Genre $genre): Genre
    {
        $genre->load('tracks');

        Log::info('Retrieved genre with tracks', [
            'genre_id' => $genre->id,
            'track_count' => $genre->tracks->count(),
        ]);

        return $genre;
    }
    
    /**
     * Find a genre by slug
     */
    public function findBySlug(string $slug): ?Genre
    {
        $genre = Genre::where('slug', $slug)->first();

        if ($genre) {
            Log::info('Genre found by slug', [
                'genre_id' => $genre->id,
                'slug' => $slug,
            ]);
        } else {
            Log::info('Genre not found by slug', [
                'slug' => $slug,
            ]);
        }

        return $genre;
    }
    
    /**
     * Create a playlist from a genre
     */
    public function createPlaylistFromGenre(PlaylistCreateFromGenreRequest $request, Genre $genre): Playlist
    {
        $validatedData = $request->validated();
        $user = Auth::user();
        
        try {
            // Start a transaction
            return DB::transaction(function () use ($genre, $user, $validatedData) {
                // Create the playlist
                $playlist = Playlist::create([
                    'title' => $validatedData['title'],
                    'description' => $validatedData['description'] ?? 'Playlist created from ' . $genre->name . ' genre',
                    'genre_id' => $genre->id,
                    'user_id' => $user->id,
                    'is_public' => $validatedData['is_public'] ?? true,
                ]);
                
                // Get all track IDs from the genre
                $trackIds = $genre->tracks()->pluck('id')->toArray();
                
                // Attach tracks with positions
                if (!empty($trackIds)) {
                    $trackData = [];
                    foreach ($trackIds as $index => $trackId) {
                        $trackData[$trackId] = ['position' => $index + 1];
                    }
                    $playlist->tracks()->attach($trackData);
                }
                
                Log::info('Playlist created from genre', [
                    'playlist_id' => $playlist->id,
                    'genre_id' => $genre->id,
                    'user_id' => $user->id,
                    'track_count' => count($trackIds),
                ]);
                
                return $playlist;
            });
        } catch (Throwable $e) {
            Log::error('Error creating playlist from genre', [
                'genre_id' => $genre->id,
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            
            throw $e;
        }
    }
    
    public function resetInputFields()
    {
        $this->name = '';
        $this->description = '';
        $this->editingGenreId = null;
    }

    /**
     * Reset the form to prepare for creating a new genre
     */
    public function createGenre()
    {
        $this->resetInputFields();
        $this->editingGenreId = null;
    }

    /**
     * Alias for resetInputFields to maintain compatibility with template
     */
    public function resetInput()
    {
        $this->resetInputFields();
    }

    public function render()
    {
        $query = Genre::query()->withCount('tracks');

        // Handle search
        if (!empty($this->search)) {
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        // Handle sorting
        $allowedSortFields = ['name', 'created_at', 'tracks_count'];
        $sortField = in_array($this->sortField, $allowedSortFields) ? $this->sortField : 'name';
        $direction = in_array($this->direction, ['asc', 'desc']) ? $this->direction : 'asc';

        $query->orderBy($sortField, $direction);
        
        return view('livewire.genres', [
            'genres' => $query->paginate($this->perPage)
        ]);
    }
} 