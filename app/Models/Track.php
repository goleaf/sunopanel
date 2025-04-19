<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

final class Track extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'url',
        'cover_image',
        'audio_url',
        'image_url',
        'unique_id',
        'duration'
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($track) {
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
    public function getNameAttribute()
    {
        return $this->attributes['title'] ?? null;
    }

    /**
     * For backward compatibility with tests that set 'name' field
     */
    public function setNameAttribute($value)
    {
        // Set title since name doesn't exist in the database
        $this->attributes['title'] = $value;
    }

    /**
     * Get the genres for the track.
     */
    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }

    /**
     * Get the playlists that contain this track.
     */
    public function playlists()
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
    public function assignGenres($genresString)
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
        foreach ($genreNames as $key => $name) {
            if (!empty($name)) {
                // Special handling for "bubblegum bass"
                if (strcasecmp(trim($name), 'bubblegum bass') === 0 || strcasecmp(trim($name), 'bubblegum-bass') === 0) {
                    $formattedName = 'Bubblegum bass';
                    $genre = Genre::firstOrCreate(['name' => $formattedName]);
                } else {
                    $genre = Genre::findOrCreateByName($name);
                }
                $this->genres()->attach($genre->id);
                Log::info('Genre attached to track', [
                    'track_id' => $this->id,
                    'track_title' => $this->title,
                    'genre_id' => $genre->id,
                    'genre_name' => $genre->name
                ]);
            }
        }
    }
    
    /**
     * Get comma-separated list of genre names.
     */
    public function getGenresListAttribute()
    {
        // Check if the relationship is loaded and is a collection
        if ($this->relationLoaded('genres') && $this->relations['genres'] instanceof \Illuminate\Database\Eloquent\Collection) {
            return $this->relations['genres']->pluck('name')->implode(', ');
        }
        
        // Return empty string as fallback
        return '';
    }

    /**
     * Get array of genre names for backward compatibility
     */
    public function getGenresArray()
    {
        // Check if the relationship is loaded and is a collection
        if ($this->relationLoaded('genres')) {
            return $this->relations['genres']->pluck('name')->toArray();
        }

        // Check if the relationship exists in the database
        $genreCount = $this->genres()->count();
        if ($genreCount > 0) {
            // Load the relationship and return the names
            $this->load('genres');
            return $this->relations['genres']->pluck('name')->toArray();
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
    public function getGenresString()
    {
        return implode(', ', $this->getGenresArray());
    }

    /**
     * Format genres string method kept for backward compatibility
     */
    public static function formatGenres($genresString)
    {
        if (empty($genresString)) {
            return '';
        }

        $genres = array_map('trim', explode(',', $genresString));
        $formattedGenres = array_map(function($genre) {
            return ucfirst($genre);
        }, $genres);

        return implode(', ', $formattedGenres);
    }

    /**
     * Generate a unique identifier for the track
     */
    public static function generateUniqueId($title)
    {
        return md5($title . time());
    }

    /**
     * Sync genres from an array or comma-separated string
     */
    public function syncGenres($genresInput)
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

        foreach ($genreNames as $key => $name) {
            if (empty($name)) continue;

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
        
        return $this;
    }

    /**
     * Get the duration in seconds 
     */
    public function getDurationSecondsAttribute()
    {
        if (!$this->duration) {
            return 0;
        }

        // Parse duration in format MM:SS
        $parts = explode(':', $this->duration);
        if (count($parts) === 2) {
            $minutes = (int)$parts[0];
            $seconds = (int)$parts[1];
            return ($minutes * 60) + $seconds;
        }

        return 0;
    }

    /**
     * Define validation rules for store requests
     */
    public function getStoreFields()
    {
        return [
            'title' => 'required|string|max:255',
            'audio_url' => 'required|url',
            'image_url' => 'required|url',
            'genres' => 'required|string',
            'duration' => 'nullable|string|regex:/^\d+:\d{2}$/',
            'bulk_tracks' => 'sometimes|string'
        ];
    }

    /**
     * Define validation rules for update requests
     */
    public function getUpdateFields()
    {
        return [
            'title' => 'required|string|max:255',
            'audio_url' => 'required|url',
            'image_url' => 'required|url',
            'genres' => 'required|string',
            'duration' => 'nullable|string|regex:/^\d+:\d{2}$/'
        ];
    }

    /**
     * Define validation rules for delete requests
     */
    public function getDeleteFields()
    {
        return [
            'id' => 'required|exists:tracks,id'
        ];
    }
}
