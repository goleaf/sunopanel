<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

final class Genre extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description'
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($genre): void {
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
    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class);
    }

    /**
     * Get the playlists for the genre.
     */
    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }
    
    /**
     * Find or create a genre by name.
     */
    public static function findOrCreateByName(string $name): self
    {
        $name = trim($name);
        Log::info("Finding or creating genre: {$name}");
        
        // Handle special cases
        $specialCases = [
            'bubblegum bass' => 'Bubblegum bass',
            'bubblegum-bass' => 'Bubblegum bass',
            'bubblegumbass' => 'Bubblegum bass',
            'drum and bass' => 'Drum and bass', 
            'drum & bass' => 'Drum and bass',
            'dnb' => 'Drum and bass',
            'symphonic metal' => 'Symphonic metal',
            'hypnotic trance' => 'Hypnotic trance'
        ];
        
        $lowercaseValue = strtolower($name);
        
        if (array_key_exists($lowercaseValue, $specialCases)) {
            $formattedName = $specialCases[$lowercaseValue];
            Log::info("Using special case formatting for: {$name} -> {$formattedName}");
        } else {
            $formattedName = ucwords(strtolower($name));
            Log::info("Standard formatting for: {$name} -> {$formattedName}");
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
    public function setNameAttribute(string $value): void
    {
        // Handle special cases
        $specialCases = [
            'bubblegum bass' => 'Bubblegum bass',
            'bubblegum-bass' => 'Bubblegum bass',
            'bubblegumbass' => 'Bubblegum bass',
            'drum and bass' => 'Drum and bass', 
            'drum & bass' => 'Drum and bass',
            'dnb' => 'Drum and bass',
            'symphonic metal' => 'Symphonic metal',
            'hypnotic trance' => 'Hypnotic trance'
        ];
        
        $lowercaseValue = strtolower(trim($value));
        
        if (array_key_exists($lowercaseValue, $specialCases)) {
            $this->attributes['name'] = $specialCases[$lowercaseValue];
        } else {
            // Use ucwords to capitalize the first letter of each word instead of just the first letter
            $this->attributes['name'] = ucwords(strtolower(trim($value)));
        }
        
        $this->attributes['slug'] = Str::slug($this->attributes['name']);
        
        Log::info('Setting genre name attribute', [
            'name' => $this->attributes['name'],
            'slug' => $this->attributes['slug'],
            'original_value' => $value
        ]);
    }
}
