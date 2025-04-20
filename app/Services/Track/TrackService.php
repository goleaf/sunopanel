<?php

declare(strict_types=1);

namespace App\Services\Track;

use App\Http\Requests\BulkTrackRequest;
use App\Http\Requests\TrackStoreRequest;
use App\Http\Requests\TrackUpdateRequest;
use App\Models\Genre;
use App\Models\Track;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class TrackService
{
    /**
     * Get paginated tracks with filtering and sorting.
     */
    public function getPaginatedTracks(Request $request): LengthAwarePaginator
    {
        $query = Track::with('genres');

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhereHas('genres', function (Builder $gq) use ($searchTerm) {
                      $gq->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Genre filter
        if ($request->filled('genre') && $request->genre) {
            $query->whereHas('genres', fn(Builder $gq) => $gq->where('genres.id', $request->genre));
        }

        // Sorting
        $sortField = $request->input('sort', 'title');
        $direction = $request->input('direction', 'asc');

        // Validate sort field
        $allowedSortFields = ['title', 'created_at', 'duration']; // Add duration if sortable
        $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'title';
        $direction = in_array($direction, ['asc', 'desc']) ? $direction : 'asc';

        $query->orderBy($sortField, $direction);

        $perPage = (int) $request->input('per_page', 15);
        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Store a new track from request data.
     */
    public function storeTrack(TrackStoreRequest $request): Track
    {
        $validated = $request->validated();

        // Create track
        $track = Track::create([
            'title' => $validated['title'],
            'audio_url' => $validated['audio_url'],
            'image_url' => $validated['image_url'] ?? null,
            'duration' => $validated['duration'] ?? null,
            'unique_id' => Track::generateUniqueId($validated['title']), // Assuming this helper exists
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
     * Update an existing track from request data.
     */
    public function updateTrack(TrackUpdateRequest $request, Track $track): Track
    {
        $validated = $request->validated();

        // Update track fields
        $track->update([
            'title' => $validated['title'],
            'audio_url' => $validated['audio_url'],
            'image_url' => $validated['image_url'] ?? $track->image_url,
            'duration' => $validated['duration'] ?? $track->duration,
            // unique_id usually shouldn't change, but handle if needed
        ]);

        // Sync genres
        if ($request->has('genre_ids')) { // Check if the key exists, even if null/empty
            $track->genres()->sync(Arr::wrap($validated['genre_ids'] ?? []));
        }

        // Sync playlists (assuming playlists can be updated this way)
        if ($request->has('playlists')) { // Check if the key exists
             $track->playlists()->sync(Arr::wrap($validated['playlists'] ?? []));
        }

        return $track->fresh(['genres', 'playlists']); // Return fresh model with relations
    }

    /**
     * Delete a track and detach relationships.
     */
    public function deleteTrack(Track $track): bool
    {
        return DB::transaction(function () use ($track) {
            // Detach from genres and playlists
            $track->genres()->detach();
            $track->playlists()->detach();

            // Delete the track
            $deleted = $track->delete();

            return (bool) $deleted;
        });
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
     * Get all genres for filter dropdowns.
     */
    public function getGenresForFilter(): \Illuminate\Database\Eloquent\Collection
    {
        return Genre::orderBy('name')->get();
    }

    /**
     * Get all tracks by popularity.
     */
    public function getPopularTracks(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Track::orderBy('play_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Increment play count for a track.
     */
    public function incrementPlayCount(Track $track): void
    {
        $track->increment('play_count');
    }

    /**
     * Get all tracks.
     */
    public function getAllTracks(): \Illuminate\Database\Eloquent\Collection
    {
        return Track::with('genres')->orderBy('title')->get();
    }
}
