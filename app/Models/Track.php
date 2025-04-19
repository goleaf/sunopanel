<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        'duration',
        'duration_seconds',
        'unique_id',
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
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['genres'];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        self::creating(function ($track): void {
            // When creating, ensure that if 'name' is used in request but 'title' is empty,
            // copy the value from 'name' to 'title'
            if (empty($track->title) && ! empty($track->name)) {
                $track->title = $track->name;
                Log::info('Track title set from name field for backward compatibility', [
                    'name' => $track->name,
                    'title' => $track->title,
                ]);
            }
        });

        self::saving(function (Track $track) {
            // Update duration_seconds when duration changes
            if ($track->isDirty('duration') && ! empty($track->duration)) {
                $track->duration_seconds = parseDuration($track->duration);
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
     * Sync genres based on a comma-separated string.
     * This will create genres that don't exist and attach them to the track.
     */
    public function syncGenres(string $genresString): void
    {
        if (empty($genresString)) {
            $this->genres()->detach();

            return;
        }

        $genreNames = array_map('trim', explode(',', $genresString));
        $genreIds = [];

        foreach ($genreNames as $name) {
            if (empty($name)) {
                continue;
            }

            // Find or create the genre with proper capitalization
            $genre = Genre::findOrCreateByName($name);
            $genreIds[] = $genre->id;
        }

        // Sync the genres to the track
        $this->genres()->sync($genreIds);
    }

    /**
     * Alias for syncGenres method to maintain backwards compatibility with tests.
     *
     * @deprecated Use syncGenres() instead
     */
    public function assignGenres(string $genresString): void
    {
        $this->syncGenres($genresString);
    }

    /**
     * Get comma-separated list of genre names.
     */
    public function getGenresListAttribute(): string
    {
        // Force loading the relationship if not already loaded
        if (!$this->relationLoaded('genres')) {
            $this->load('genres');
        }
        
        // Return the imploded genre names
        return $this->genres->pluck('name')->implode(', ');
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
        if (! empty($this->getAttribute('genres'))) {
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
        $formattedGenres = array_map(fn ($genre) => ucfirst($genre), $genres);

        return implode(', ', $formattedGenres);
    }

    /**
     * Generate a unique ID for the track.
     */
    public static function generateUniqueId(string $title): string
    {
        $baseSlug = Str::slug($title);
        $uniqueId = $baseSlug;
        $counter = 1;

        // Make sure the ID is unique
        while (self::where('unique_id', $uniqueId)->exists()) {
            $uniqueId = $baseSlug.'-'.$counter++;
        }

        return $uniqueId;
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
            $minutes = (int) $parts[0];
            $seconds = (int) $parts[1];

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
        return ['title', 'audio_url', 'image_url', 'duration', 'unique_id'];
    }

    /**
     * Get the fields used for updating a track
     *
     * @return array<string, mixed>
     */
    public function getUpdateFields(): array
    {
        return ['title', 'audio_url', 'image_url', 'duration'];
    }

    /**
     * Get fields for deletion logging
     *
     * @return array<string, mixed>
     */
    public function getDeleteFields(): array
    {
        return ['id', 'title', 'unique_id'];
    }
}
