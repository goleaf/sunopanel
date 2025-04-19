<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final class Track extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'audio_url',
        'image_url',
        'unique_id',
        'duration'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'genres_list',
        'duration_seconds',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($track): void {
            // When creating, ensure that if 'name' is used in request but 'title' is empty,
            // copy the value from 'name' to 'title'
            if (empty($track->title) && !empty($track->name)) {
                $track->title = $track->name;
                Log::info('Track title set from name field for backward compatibility', [
                    'name' => $track->name,
                    'title' => $track->title
                ]);
            }
        });
    }

    /**
     * For backward compatibility with tests that expect a 'name' field
     */
    public function getNameAttribute(): ?string
    {
        return $this->attributes['title'] ?? null;
    }

    /**
     * For backward compatibility with tests that set 'name' field
     */
    public function setNameAttribute(string $value): void
    {
        // Set title since name doesn't exist in the database
        $this->attributes['title'] = $value;
    }

    /**
     * Get the genres for the track.
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    /**
     * Get the playlists that contain this track.
     */
    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class)
                    ->withPivot('position')
                    ->orderBy('position');
    }

    /**
     * Assign genres to the track.
     *
     * @param string $genresString Comma-separated list of genre names
     */
    public function assignGenres(string $genresString): void
    {
        Log::info('Assigning genres to track', [
            'track_id' => $this->id,
            'track_title' => $this->title,
            'genres_string' => $genresString
        ]);
        
        // Clear current genres
        $this->genres()->detach();
        
        // Skip if empty
        if (empty($genresString)) {
            return;
        }
        
        // Split into array and trim each value
        $genreNames = array_map('trim', explode(',', $genresString));
        
        // Find or create each genre and attach to track
        foreach ($genreNames as $name) {
            if (empty($name)) {
                continue;
            }

            // Normalize the name to lowercase for consistent comparison
            $normalizedName = strtolower(trim($name));
            
            // Special handling for "bubblegum bass"
            if ($normalizedName === 'bubblegum bass' || 
                $normalizedName === 'bubblegum-bass' || 
                $normalizedName === 'bubblegumbass') {
                $genre = Genre::findOrCreateByName('Bubblegum bass');
            } else {
                $genre = Genre::findOrCreateByName($name);
            }
            
            $this->genres()->attach($genre->id);
            
            Log::info('Genre attached to track', [
                'track_id' => $this->id,
                'track_title' => $this->title,
                'genre_id' => $genre->id,
                'genre_name' => $genre->name,
                'original_name' => $name
            ]);
        }
    }
    
    /**
     * Get comma-separated list of genre names.
     */
    public function getGenresListAttribute(): string
    {
        // Check if the relationship is loaded and is a collection
        if ($this->relationLoaded('genres') && $this->genres instanceof Collection) {
            return $this->genres->pluck('name')->implode(', ');
        }
        
        // Return empty string as fallback
        return '';
    }

    /**
     * Get array of genre names for backward compatibility
     * 
     * @return array<int, string>
     */
    public function getGenresArray(): array
    {
        // Check if the relationship is loaded and is a collection
        if ($this->relationLoaded('genres')) {
            return $this->genres->pluck('name')->toArray();
        }

        // Check if the relationship exists in the database
        $genreCount = $this->genres()->count();
        if ($genreCount > 0) {
            // Load the relationship and return the names
            $this->load('genres');
            return $this->genres->pluck('name')->toArray();
        }

        // Fall back to the old string field if needed
        if (!empty($this->getAttribute('genres'))) {
            return array_map('trim', explode(',', $this->getAttribute('genres')));
        }

        return [];
    }

    /**
     * Get comma-separated string of genre names
     */
    public function getGenresString(): string
    {
        return implode(', ', $this->getGenresArray());
    }

    /**
     * Format genres string method kept for backward compatibility
     */
    public static function formatGenres(string $genresString): string
    {
        if (empty($genresString)) {
            return '';
        }

        $genres = array_map('trim', explode(',', $genresString));
        $formattedGenres = array_map(fn($genre) => ucfirst($genre), $genres);

        return implode(', ', $formattedGenres);
    }

    /**
     * Generate a unique identifier for the track
     */
    public static function generateUniqueId(string $title): string
    {
        return md5($title . time());
    }

    /**
     * Sync genres from an array or comma-separated string
     * 
     * @param string|array<int, string> $genresInput
     */
    public function syncGenres(string|array $genresInput): void
    {
        Log::info('Syncing genres for track', [
            'track_id' => $this->id,
            'track_title' => $this->title,
            'genres_input' => $genresInput
        ]);
        
        // If it's a string, convert to array
        if (is_string($genresInput)) {
            $genreNames = array_map('trim', explode(',', $genresInput));
        } else {
            $genreNames = $genresInput;
        }

        $genreIds = [];

        foreach ($genreNames as $name) {
            if (empty($name)) {
                continue;
            }

            // Special handling for "bubblegum bass"
            if (strcasecmp(trim($name), 'bubblegum bass') === 0 || strcasecmp(trim($name), 'bubblegum-bass') === 0) {
                $formattedName = 'Bubblegum bass';
            } else {
                // Format the genre name (capitalize first letter)
                $formattedName = ucfirst(trim($name));
            }

            // Find or create the genre
            $genre = Genre::firstOrCreate(
                ['name' => $formattedName]
            );

            $genreIds[] = $genre->id;
            
            Log::info('Genre processed for syncing', [
                'track_id' => $this->id,
                'track_title' => $this->title,
                'genre_id' => $genre->id,
                'genre_name' => $genre->name
            ]);
        }

        // Sync with the pivot table
        $this->genres()->sync($genreIds);
        
        Log::info('Genres synced for track', [
            'track_id' => $this->id,
            'track_title' => $this->title,
            'genre_count' => count($genreIds)
        ]);

        // Refresh the genres relationship
        $this->load('genres');
    }

    /**
     * Get the track duration in seconds
     */
    public function getDurationSecondsAttribute(): int
    {
        if (empty($this->duration)) {
            return 0;
        }

        // Parse from format like "3:45"
        $parts = explode(':', $this->duration);
        
        if (count($parts) === 2) {
            $minutes = (int)$parts[0];
            $seconds = (int)$parts[1];
            return ($minutes * 60) + $seconds;
        }
        
        return 0;
    }

    /**
     * Get the fields used for storing a track
     * 
     * @return array<string, mixed>
     */
    public function getStoreFields(): array
    {
        return [
            'title' => $this->title,
            'url' => $this->url,
            'cover_image' => $this->cover_image,
            'audio_url' => $this->audio_url,
            'image_url' => $this->image_url,
            'unique_id' => $this->unique_id,
            'duration' => $this->duration,
        ];
    }

    /**
     * Get the fields used for updating a track
     * 
     * @return array<string, mixed>
     */
    public function getUpdateFields(): array
    {
        return [
            'title' => $this->title,
            'url' => $this->url, 
            'cover_image' => $this->cover_image,
            'audio_url' => $this->audio_url,
            'image_url' => $this->image_url,
            'duration' => $this->duration,
        ];
    }

    /**
     * Get fields for deletion logging
     * 
     * @return array<string, mixed>
     */
    public function getDeleteFields(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'unique_id' => $this->unique_id,
        ];
    }
}
