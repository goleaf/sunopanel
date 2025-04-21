<?php

namespace App\Http\Livewire;

use App\Http\Requests\PlaylistListRequest;
use App\Http\Requests\PlaylistRequest;
use App\Http\Requests\PlaylistStoreTracksRequest;
use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Track;
use App\Traits\WithNotifications;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;
use Livewire\Attributes\Title;

class Playlists extends Component
{
    use WithPagination;
    use WithNotifications;
    use WithFileUploads;

    /**
     * Indicates if the component should be rendered on the server.
     *
     * @var bool
     */
    protected bool $shouldRenderOnServer = true;

    public $search = '';
    public $sortField = 'created_at';
    public $direction = 'desc';
    public $perPage = 10;
    public $genreFilter = '';
    public $showDeleteModal = false;
    public $playlistIdToDelete = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'direction' => ['except' => 'desc'],
        'genreFilter' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];
    
    /**
     * Get cache key for playlists query
     */
    private function getCacheKey(): string
    {
        return 'playlists_' . 
               md5($this->search . 
                  '_' . $this->genreFilter . 
                  '_' . $this->perPage . 
                  '_' . $this->sortField . 
                  '_' . $this->direction . 
                  '_' . $this->page);
    }
    
    /**
     * Build playlists query with filters
     */
    private function buildPlaylistsQuery()
    {
        $query = Playlist::with(['tracks', 'genre']);
            
        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }
        
        // Apply genre filter
        if (!empty($this->genreFilter)) {
            $query->where('genre_id', $this->genreFilter);
        }
        
        // Apply sorting
        $query->orderBy($this->sortField, $this->direction);
        
        return $query;
    }
    
    /**
     * Get cached playlists with pagination
     */
    private function getCachedPlaylists()
    {
        $cacheKey = $this->getCacheKey();
        $cacheTtl = 5; // Cache for 5 minutes
        
        return Cache::remember($cacheKey, $cacheTtl, function () {
            return $this->buildPlaylistsQuery()->paginate($this->perPage);
        });
    }
    
    /**
     * Get genres for filter, with caching
     */
    private function getGenresForFilter()
    {
        return Cache::remember('playlist_genres_for_filter', 60, function () {
            return Genre::orderBy('name')->get();
        });
    }
    
    /**
     * Clear playlists cache when manipulating playlist data
     */
    private function clearPlaylistsCache(): void
    {
        // Clear all playlists cache, as changes to one playlist might affect multiple pages
        $cacheKeys = Cache::get('playlists_cache_keys', []);
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        
        // Clear the cache keys list itself
        Cache::forget('playlists_cache_keys');
        
        // Also clear genres cache
        Cache::forget('playlist_genres_for_filter');
        
        // Clear individual playlist cache if it exists
        if ($this->playlistIdToDelete) {
            Cache::forget('playlist_' . $this->playlistIdToDelete);
        }
    }
    
    /**
     * Store playlist cache key for later clearing
     */
    private function storeCacheKey(): void
    {
        $cacheKey = $this->getCacheKey();
        $cacheKeys = Cache::get('playlists_cache_keys', []);
        
        if (!in_array($cacheKey, $cacheKeys)) {
            $cacheKeys[] = $cacheKey;
            Cache::put('playlists_cache_keys', $cacheKeys, 60 * 24); // Store for 24 hours
        }
    }
    
    protected function rules()
    {
        return (new PlaylistListRequest())->rules();
    }
    
    protected function messages()
    {
        return (new PlaylistListRequest())->messages();
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

    public function updatingGenreFilter()
    {
        $this->resetPage();
    }

    public function confirmDelete($playlistId)
    {
        $this->validate(['playlistIdToDelete' => 'exists:playlists,id'], 
            [], ['playlistIdToDelete' => $playlistId]);
        
        $this->playlistIdToDelete = $playlistId;
        $this->showDeleteModal = true;
    }
    
    public function cancelDelete()
    {
        $this->playlistIdToDelete = null;
        $this->showDeleteModal = false;
    }
    
    public function deletePlaylist()
    {
        if (!$this->playlistIdToDelete) {
            return;
        }
        
        $this->validate(['playlistIdToDelete' => 'exists:playlists,id']);
        
        $playlist = Playlist::findOrFail($this->playlistIdToDelete);
        $playlistTitle = $playlist->title;
        
        try {
            $deleted = $this->deletePlaylistAndDetachTracks($playlist);
            
            if ($deleted) {
                // Clear cache after successful deletion
                $this->clearPlaylistsCache();
                
                $this->notifySuccess("Playlist '{$playlistTitle}' deleted successfully.");
            } else {
                $this->notifyError("Failed to delete playlist '{$playlistTitle}'.");
            }
            
            $this->playlistIdToDelete = null;
            $this->showDeleteModal = false;
        } catch (Throwable $e) {
            Log::error("Error deleting playlist", [
                'playlist_id' => $this->playlistIdToDelete,
                'error' => $e->getMessage(),
                'user_id' => Auth::id() ?? 'guest'
            ]);
            
            $this->notifyError('Error deleting playlist: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete a playlist and detach its tracks within a transaction.
     */
    public function deletePlaylistAndDetachTracks(Playlist $playlist): bool
    {
        $playlistId = $playlist->id;
        $playlistTitle = $playlist->title;

        return DB::transaction(function () use ($playlist, $playlistId, $playlistTitle) {
            // Detach tracks
            $detachedCount = $playlist->tracks()->detach();
            Log::info('Tracks detached from playlist before deletion', [
                'playlist_id' => $playlistId,
                'detached_count' => $detachedCount,
            ]);

            // Delete cover image if exists
            if ($playlist->cover_path) {
                Storage::disk('public')->delete($playlist->cover_path);
                Log::info('Playlist cover deleted', [
                    'playlist_id' => $playlistId,
                    'cover_path' => $playlist->cover_path,
                ]);
            }

            // Delete the playlist
            $deleted = $playlist->delete();

            if ($deleted) {
                Log::info('Playlist deleted successfully', [
                    'playlist_id' => $playlistId, 
                    'title' => $playlistTitle
                ]);
            } else {
                Log::warning('Failed to delete playlist model', ['playlist_id' => $playlistId]);
            }
            
            return (bool) $deleted;
        });
    }
    
    /**
     * Store a new playlist from validated data.
     */
    public function storeFromArray(array $validatedData): Playlist
    {
        $coverPath = null;
        if (isset($validatedData['cover_image']) && $validatedData['cover_image'] instanceof UploadedFile) {
            $coverPath = $this->storeCoverImage($validatedData['cover_image']);
        }

        // Create the playlist using validated data
        $playlist = Playlist::create([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'] ?? null,
            'genre_id' => $validatedData['genre_id'] ?? null,
            'user_id' => Auth::id(),
            'cover_path' => $coverPath,
            'is_public' => $validatedData['is_public'] ?? true,
        ]);

        // Clear cache after creating a new playlist
        $this->clearPlaylistsCache();

        Log::info('Playlist created from array data', [
            'playlist_id' => $playlist->id,
            'title' => $playlist->title,
            'user_id' => Auth::id(),
        ]);

        return $playlist;
    }
    
    /**
     * Update an existing playlist from validated data.
     */
    public function updateFromArray(array $validatedData, Playlist $playlist): Playlist
    {
        $updateData = [
            'title' => $validatedData['title'],
            'description' => $validatedData['description'] ?? null,
            'genre_id' => $validatedData['genre_id'] ?? null,
            'is_public' => $validatedData['is_public'] ?? $playlist->is_public,
        ];

        // Handle cover image upload if provided
        if (isset($validatedData['cover_image']) && $validatedData['cover_image'] instanceof UploadedFile) {
            // Delete old cover if exists
            if ($playlist->cover_path) {
                Storage::disk('public')->delete($playlist->cover_path);
            }
            $updateData['cover_path'] = $this->storeCoverImage($validatedData['cover_image']);
        } elseif (isset($validatedData['remove_cover_image']) && $validatedData['remove_cover_image']) {
            if ($playlist->cover_path) {
                Storage::disk('public')->delete($playlist->cover_path);
                $updateData['cover_path'] = null;
                Log::info('Playlist cover removed during update', ['playlist_id' => $playlist->id]);
            }
        }

        $playlist->update($updateData);

        // Clear cache after updating a playlist
        Cache::forget('playlist_' . $playlist->id);
        $this->clearPlaylistsCache();

        Log::info('Playlist updated from array data', [
            'playlist_id' => $playlist->id,
            'title' => $playlist->title,
        ]);

        return $playlist->fresh();
    }
    
    /**
     * Get playlist details including tracks and total duration.
     */
    public function getPlaylistWithTrackDetails(Playlist $playlist): Playlist
    {
        // Use cache for individual playlist details
        return Cache::remember('playlist_' . $playlist->id, 5, function () use ($playlist) {
            $playlist->load([
                'tracks' => function ($query) {
                    $query->with('genres')->orderBy('playlist_track.position', 'asc');
                },
                'genre',
            ]);
            
            return $playlist;
        });
    }

    /**
     * Add tracks to a playlist.
     */
    public function addTracks(Playlist $playlist, array $trackIds): Playlist
    {
        // If the playlist already has tracks, get the highest position
        $maxPosition = $playlist->tracks()->max('playlist_track.position') ?: 0;
        
        $tracksToAttach = [];
        foreach ($trackIds as $index => $trackId) {
            $tracksToAttach[$trackId] = ['position' => $maxPosition + $index + 1];
        }
        
        if (!empty($tracksToAttach)) {
            $playlist->tracks()->attach($tracksToAttach);
            
            // Reorder to ensure consistent positions
            $this->reorderPlaylistTracks($playlist);
            
            Log::info('Tracks added to playlist', [
                'playlist_id' => $playlist->id,
                'tracks_added' => count($tracksToAttach)
            ]);
        }
        
        // Clear cache after adding tracks
        Cache::forget('playlist_' . $playlist->id);
        $this->clearPlaylistsCache();
        
        return $playlist->fresh(['tracks']);
    }
    
    /**
     * Remove multiple tracks from a playlist.
     */
    public function removeTracks(Playlist $playlist, array $trackIds): Playlist
    {
        $count = $playlist->tracks()->detach($trackIds);
        
        if ($count > 0) {
            // Reorder the remaining tracks
            $this->reorderPlaylistTracks($playlist);
            
            Log::info('Tracks removed from playlist', [
                'playlist_id' => $playlist->id,
                'tracks_removed' => $count
            ]);
        }
        
        // Clear cache after removing tracks
        Cache::forget('playlist_' . $playlist->id);
        $this->clearPlaylistsCache();
        
        return $playlist->fresh(['tracks']);
    }
    
    /**
     * Reorder playlist tracks to ensure sequential positions.
     */
    private function reorderPlaylistTracks(Playlist $playlist): void
    {
        $tracks = $playlist->tracks()->orderBy('playlist_track.position')->get();
        
        DB::transaction(function () use ($tracks, $playlist) {
            foreach ($tracks as $index => $track) {
                DB::table('playlist_track')
                    ->where('playlist_id', $playlist->id)
                    ->where('track_id', $track->id)
                    ->update(['position' => $index + 1]);
            }
        });
    }
    
    /**
     * Remove a single track from a playlist.
     */
    public function removeTrack(Playlist $playlist, Track $track): bool
    {
        try {
            $removed = (bool) $playlist->tracks()->detach($track->id);
            
            if ($removed) {
                // Reorder positions to maintain sequence
                $this->reorderPlaylistTracks($playlist);
                
                Log::info('Track removed from playlist', [
                    'playlist_id' => $playlist->id,
                    'track_id' => $track->id
                ]);
            }
            
            // Clear cache after removing track
            Cache::forget('playlist_' . $playlist->id);
            $this->clearPlaylistsCache();
            
            return $removed;
        } catch (Throwable $e) {
            Log::error('Error removing track from playlist', [
                'playlist_id' => $playlist->id,
                'track_id' => $track->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Create a new playlist from a genre.
     */
    public function createFromGenre(Genre $genre, array $validatedData): Playlist
    {
        try {
            DB::beginTransaction();
            
            // Create the playlist
            $playlist = Playlist::create([
                'title' => $validatedData['title'] ?? "{$genre->name} Playlist",
                'description' => $validatedData['description'] ?? "Playlist created from {$genre->name} genre",
                'genre_id' => $genre->id,
                'user_id' => Auth::id(),
                'is_public' => $validatedData['is_public'] ?? true,
            ]);
            
            // Add all tracks from the genre if requested
            if (isset($validatedData['add_all_tracks']) && $validatedData['add_all_tracks']) {
                $trackIds = Track::whereHas('genres', function ($query) use ($genre) {
                    $query->where('genres.id', $genre->id);
                })->pluck('id')->toArray();
                
                if (!empty($trackIds)) {
                    $this->addTracks($playlist, $trackIds);
                    Log::info('Added genre tracks to new playlist', [
                        'playlist_id' => $playlist->id,
                        'genre_id' => $genre->id,
                        'track_count' => count($trackIds)
                    ]);
                }
            }
            
            DB::commit();
            
            Log::info('Playlist created from genre', [
                'playlist_id' => $playlist->id, 
                'genre_id' => $genre->id,
                'title' => $playlist->title
            ]);
            
            return $playlist;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Failed to create playlist from genre', [
                'genre_id' => $genre->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Update track positions in a playlist.
     */
    public function updateTrackPositions(Playlist $playlist, array $trackPositions): bool
    {
        try {
            DB::beginTransaction();
            
            foreach ($trackPositions as $trackId => $position) {
                DB::table('playlist_track')
                    ->where('playlist_id', $playlist->id)
                    ->where('track_id', $trackId)
                    ->update(['position' => $position]);
            }
            
            DB::commit();
            
            Log::info('Playlist track positions updated', [
                'playlist_id' => $playlist->id,
                'tracks_updated' => count($trackPositions)
            ]);
            
            return true;
        } catch (Throwable $e) {
            DB::rollBack();
            
            Log::error('Error updating track positions', [
                'playlist_id' => $playlist->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Store a playlist cover image and return the path.
     */
    private function storeCoverImage(UploadedFile $file): string
    {
        $filename = 'playlist_cover_' . Str::random(15) . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('playlist_covers', $filename, 'public');
    }
    
    /**
     * Add tracks from a form request.
     */
    public function addTracksFromRequest(PlaylistStoreTracksRequest $request, Playlist $playlist): int
    {
        $validated = $request->validated();
        
        if (!isset($validated['track_ids']) || empty($validated['track_ids'])) {
            return 0;
        }
        
        $trackIds = $validated['track_ids'];
        
        // Filter out tracks already in the playlist
        $existingTrackIds = $playlist->tracks->pluck('id')->toArray();
        $newTrackIds = array_diff($trackIds, $existingTrackIds);
        
        if (empty($newTrackIds)) {
            return 0;
        }
        
        $this->addTracks($playlist, $newTrackIds);
        
        return count($newTrackIds);
    }
    
    /**
     * Render the component
     */
    #[Title('Playlists Management')]
    public function render()
    {
        $this->validate();
        
        // Store current cache key for potential future clearing
        $this->storeCacheKey();
        
        $playlists = $this->getCachedPlaylists();
        $genres = $this->getGenresForFilter();
        
        return view('livewire.playlists', [
            'playlists' => $playlists,
            'genres' => $genres,
        ]);
    }
} 