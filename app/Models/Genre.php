<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Logging\LoggingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

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
        'description',
    ];

    /**
     * Special case genres with specific capitalization rules
     *
     * @var array<string, string>
     */
    private static array $specialCaseGenres = [
        'bubblegum bass' => 'Bubblegum bass',
        'bubblegum-bass' => 'Bubblegum bass',
        'bubblegumbass' => 'Bubblegum bass',
        'drum and bass' => 'Drum and bass',
        'drum & bass' => 'Drum and bass',
        'dnb' => 'Drum and bass',
        'edm' => 'EDM',
        'uk garage' => 'UK Garage',
        'r&b' => 'R&B',
        'symphonic metal' => 'Symphonic metal',
        'hypnotic trance' => 'Hypnotic trance',
        'idm' => 'IDM',
        'uk drill' => 'UK Drill',
        'uk grime' => 'UK Grime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function ($genre): void {
            $genre->slug = Str::slug($genre->name);
            $logger = App::make(LoggingService::class);
            $logger->info('Creating new genre', [
                'name' => $genre->name,
                'slug' => $genre->slug,
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
     * Find or create a genre by name with proper capitalization
     */
    public static function findOrCreateByName(string $name): self
    {
        $name = trim($name);
        $logger = App::make(LoggingService::class);
        $logger->info("Finding or creating genre: {$name}");

        // Format the name with proper capitalization
        $formattedName = self::formatGenreName($name);

        // First check if a genre with this name (case-insensitive) already exists
        $existingGenre = self::whereRaw('LOWER(name) = ?', [strtolower($formattedName)])->first();

        if ($existingGenre) {
            $logger->info("Found existing genre: {$existingGenre->name}", [
                'id' => $existingGenre->id,
                'slug' => $existingGenre->slug,
            ]);

            // Ensure the existing genre has the proper capitalization
            if ($existingGenre->name !== $formattedName) {
                $existingGenre->name = $formattedName;
                $existingGenre->save();
                $logger->info('Updated genre name capitalization', [
                    'id' => $existingGenre->id,
                    'old_name' => $existingGenre->name,
                    'new_name' => $formattedName,
                ]);
            }

            return $existingGenre;
        }

        // Check if a genre with the same slug already exists
        $slug = Str::slug($formattedName);
        $existingBySlug = self::where('slug', $slug)->first();

        if ($existingBySlug) {
            $logger->info("Found existing genre by slug: {$existingBySlug->name}", [
                'id' => $existingBySlug->id,
                'slug' => $existingBySlug->slug,
            ]);

            // Ensure the existing genre has the proper capitalization
            if ($existingBySlug->name !== $formattedName) {
                $existingBySlug->name = $formattedName;
                $existingBySlug->save();
                $logger->info('Updated genre name capitalization', [
                    'id' => $existingBySlug->id,
                    'old_name' => $existingBySlug->name,
                    'new_name' => $formattedName,
                ]);
            }

            return $existingBySlug;
        }

        // Create a new genre
        $genre = self::create([
            'name' => $formattedName,
            'slug' => $slug,
            'description' => "Genre for {$formattedName} music",
        ]);

        $logger->info('Created new genre', [
            'id' => $genre->id,
            'name' => $genre->name,
            'slug' => $genre->slug,
        ]);

        return $genre;
    }

    /**
     * Set the name attribute and automatically generate the slug.
     */
    public function setNameAttribute(string $value): void
    {
        $formattedName = self::formatGenreName($value);
        $this->attributes['name'] = $formattedName;
        $this->attributes['slug'] = Str::slug($formattedName);

        $logger = App::make(LoggingService::class);
        $logger->info('Setting genre name attribute', [
            'name' => $this->attributes['name'],
            'slug' => $this->attributes['slug'],
            'original_value' => $value,
        ]);
    }

    /**
     * Format a genre name with the proper capitalization
     */
    public static function formatGenreName(string $name): string
    {
        $name = trim($name);
        $lowercaseValue = strtolower($name);

        // Check for special cases first
        if (array_key_exists($lowercaseValue, self::$specialCaseGenres)) {
            return self::$specialCaseGenres[$lowercaseValue];
        }

        // Special handling for multi-word genres
        if (str_contains($lowercaseValue, ' ')) {
            // Make sure conjunctions, articles, and prepositions remain lowercase
            $words = explode(' ', $lowercaseValue);
            $result = [];

            $lowercase = ['and', 'or', 'the', 'in', 'on', 'at', 'by', 'for', 'with', 'a', 'an', 'of'];

            foreach ($words as $i => $word) {
                // First word and any word not in the lowercase list should be capitalized
                if ($i === 0 || ! in_array($word, $lowercase)) {
                    $result[] = ucfirst($word);
                } else {
                    $result[] = $word;
                }
            }

            return implode(' ', $result);
        }

        // Single word genres are simply capitalized
        return ucfirst($lowercaseValue);
    }
}
