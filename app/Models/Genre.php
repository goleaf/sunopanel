<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

final class Genre extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description'
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($genre) {
            $genre->slug = Str::slug($genre->name);
            Log::info('Creating new genre', [
                'name' => $genre->name,
                'slug' => $genre->slug
            ]);
        });
    }

    /**
     * Get the tracks for the genre.
     */
    public function tracks()
    {
        return $this->belongsToMany(Track::class);
    }

    /**
     * Get the playlists for the genre.
     */
    public function playlists()
    {
        return $this->hasMany(Playlist::class);
    }
    
    /**
     * Find or create a genre by name.
     */
    public static function findOrCreateByName($name)
    {
        $name = trim($name);
        Log::info("Finding or creating genre: {$name}");
        
        // Special case for "bubblegum bass"
        if (strcasecmp($name, 'bubblegum bass') === 0 || strcasecmp($name, 'bubblegum-bass') === 0) {
            $formattedName = 'Bubblegum bass';
        } else {
            $formattedName = ucfirst(strtolower($name));
        }
        
        // First check if a genre with this name (case-insensitive) already exists
        $existingGenre = static::whereRaw('LOWER(name) = ?', [strtolower($formattedName)])->first();
        
        if ($existingGenre) {
            Log::info("Found existing genre: {$existingGenre->name}", [
                'id' => $existingGenre->id,
                'slug' => $existingGenre->slug
            ]);
            return $existingGenre;
        }
        
        // Check if a genre with the same slug already exists
        $slug = Str::slug($formattedName);
        $existingBySlug = static::where('slug', $slug)->first();
        
        if ($existingBySlug) {
            Log::info("Found existing genre by slug: {$existingBySlug->name}", [
                'id' => $existingBySlug->id,
                'slug' => $existingBySlug->slug
            ]);
            return $existingBySlug;
        }
        
        // Create a new genre
        $genre = static::create([
            'name' => $formattedName,
            'slug' => $slug,
            'description' => "Genre for {$formattedName} music"
        ]);
        
        Log::info("Created new genre", [
            'id' => $genre->id,
            'name' => $genre->name,
            'slug' => $genre->slug
        ]);
        
        return $genre;
    }

    /**
     * Set the name attribute and automatically generate the slug.
     */
    public function setNameAttribute($value)
    {
        // Special case for "bubblegum bass"
        if (strcasecmp(trim($value), 'bubblegum bass') === 0 || strcasecmp(trim($value), 'bubblegum-bass') === 0) {
            $this->attributes['name'] = 'Bubblegum bass';
        } else {
            $this->attributes['name'] = ucfirst(strtolower(trim($value)));
        }
        
        $this->attributes['slug'] = Str::slug($this->attributes['name']);
        
        Log::info('Setting genre name attribute', [
            'name' => $this->attributes['name'],
            'slug' => $this->attributes['slug'],
            'original_value' => $value
        ]);
    }
}
