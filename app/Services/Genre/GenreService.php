<?php

declare(strict_types=1);

namespace App\Services\Genre;

use App\Http\Requests\GenreStoreRequest;
use App\Http\Requests\GenreUpdateRequest;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;

final readonly class GenreService
{
    /**
     * Store a new genre
     */
    public function store(GenreStoreRequest $request): Genre
    {
        return $this->storeFromArray($request->validated());
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

    /**
     * Update an existing genre
     */
    public function update(GenreUpdateRequest $request, Genre $genre): Genre
    {
        return $this->updateFromArray($request->validated(), $genre);
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

    /**
     * Delete a genre
     */
    public function delete(Genre $genre): bool
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
     * Get all genres with pagination
     */
    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        $genres = Genre::withCount('tracks')
            ->orderBy('name')
            ->paginate($perPage);

        Log::info('Retrieved all genres', [
            'count' => $genres->total(),
        ]);

        return $genres;
    }

    /**
     * Get genres with track counts
     */
    public function getWithTrackCounts(): array
    {
        $genres = Genre::withCount('tracks')
            ->orderBy('name')
            ->get();

        Log::info('Retrieved genres with track counts', [
            'count' => $genres->count(),
        ]);

        return $genres->toArray();
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
     * Get paginated genres with filtering and sorting.
     */
    public function getPaginatedGenres(Request $request): LengthAwarePaginator
    {
        $query = Genre::query()->withCount('tracks');

        // Handle search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', '%'.$search.'%');
        }

        // Handle sorting
        $sortField = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');

        // Validate sort field
        $allowedSortFields = ['name', 'tracks_count', 'created_at'];
        $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'name';
        $direction = in_array($direction, ['asc', 'desc']) ? $direction : 'asc';

        $query->orderBy($sortField, $direction);

        // Default pagination size
        $perPage = (int) $request->input('per_page', 15);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get paginated tracks for a specific genre with sorting.
     */
    public function getPaginatedTracksForGenre(Genre $genre, Request $request): LengthAwarePaginator
    {
        $query = $genre->tracks()->with('genres'); // Eager load genres for tracks

        // Handle sorting
        $sortField = $request->query('sort', 'title');
        $direction = $request->query('direction', 'asc');

        // Validate sort field
        $allowedSortFields = ['title', 'created_at', 'duration'];
        $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'title';
        $direction = in_array($direction, ['asc', 'desc']) ? $direction : 'asc';

        $query->orderBy($sortField, $direction);

        // Default pagination size
        $perPage = (int) $request->input('per_page', 10);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Delete a genre after detaching its tracks.
     */
    public function deleteGenreAndDetachTracks(Genre $genre): bool
    {
        $genreId = $genre->id;
        $genreName = $genre->name;

        return DB::transaction(function () use ($genre) {
            $trackCount = $genre->tracks()->count();

            // Detach all tracks associated with this genre
            if ($trackCount > 0) {
                $genre->tracks()->detach();
                Log::info('Detached tracks from genre before deletion', [
                    'genre_id' => $genre->id,
                    'name' => $genre->name,
                    'detached_tracks' => $trackCount,
                ]);
            }

            // Now delete the genre
            $deleted = $genre->delete();

            if ($deleted) {
                Log::info('Genre deleted successfully after detaching tracks', [
                    'genre_id' => $genre->id,
                    'name' => $genre->name,
                ]);
            } else {
                Log::warning('Failed to delete genre after detaching tracks', [
                    'genre_id' => $genre->id,
                    'name' => $genre->name,
                ]);
            }
            return (bool) $deleted;
        });
    }

    /**
     * Get all genres
     *
     * @return Collection
     */
    public function getAllGenres(): Collection
    {
        return Genre::orderBy('name')->get();
    }
    
    /**
     * Create a new genre
     *
     * @param array $data
     * @param mixed $user
     * @return Genre
     */
    public function createGenre(array $data, $user): Genre
    {
        return Genre::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * Update an existing genre
     *
     * @param int $id
     * @param array $data
     * @param mixed $user
     * @return Genre
     */
    public function updateGenre(int $id, array $data, $user): Genre
    {
        $genre = Genre::findOrFail($id);
        $genre->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
        
        return $genre;
    }

    /**
     * Delete a genre
     *
     * @param int $id
     * @param mixed $user
     * @return bool
     */
    public function deleteGenre(int $id, $user): bool
    {
        $genre = Genre::findOrFail($id);
        return $genre->delete();
    }
}
