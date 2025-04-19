<?php

declare(strict_types=1);

namespace App\Services\Genre;

use App\Http\Requests\GenreStoreRequest;
use App\Http\Requests\GenreUpdateRequest;
use App\Models\Genre;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class GenreService
{
    /**
     * Store a new genre
     */
    public function store(GenreStoreRequest $request): Genre
    {
        $validatedData = $request->validated();
        
        // Generate a slug from the name if not provided
        if (!isset($validatedData['slug'])) {
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
            'slug' => $genre->slug
        ]);
        
        return $genre;
    }
    
    /**
     * Update an existing genre
     */
    public function update(GenreUpdateRequest $request, Genre $genre): Genre
    {
        $validatedData = $request->validated();
        
        // Update slug if name changes and slug is not explicitly provided
        if (isset($validatedData['name']) && $validatedData['name'] !== $genre->name && !isset($validatedData['slug'])) {
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
            'slug' => $genre->slug
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
                'tracks_count' => $tracksCount
            ]);
            
            return false;
        }
        
        $deleted = $genre->delete();
        
        Log::info('Genre deleted', [
            'genre_id' => $genre->id,
            'name' => $genre->name,
            'success' => $deleted
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
            'count' => $genres->total()
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
            'count' => $genres->count()
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
            'track_count' => $genre->tracks->count()
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
                'slug' => $slug
            ]);
        } else {
            Log::info('Genre not found by slug', [
                'slug' => $slug
            ]);
        }
        
        return $genre;
    }
} 