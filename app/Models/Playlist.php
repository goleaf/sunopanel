<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;

final class Playlist extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'cover_image',
        'cover_path',
        'genre_id',
        'user_id',
        'is_public',
        'slug',
    ];

    /**
     * Get the tracks for the playlist.
     */
    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class)
            ->withPivot('position')
            ->orderBy('position');
    }

    /**
     * Get the genre that owns the playlist.
     */
    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
    }

    /**
     * Get the user that owns the playlist.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all of the genres for the playlist through its tracks.
     */
    public function genres(): array
    {
        $genres = [];
        foreach ($this->tracks as $track) {
            $trackGenres = $track->genres;
            foreach ($trackGenres as $genre) {
                $genres[$genre->id] = $genre;
            }
        }

        return array_values($genres);
    }

    /**
     * Get the name attribute for backward compatibility.
     */
    public function getNameAttribute(): string
    {
        Log::info('Using deprecated name attribute. Use title instead.', [
            'class' => self::class,
            'id' => $this->id,
        ]);

        return $this->title;
    }

    /**
     * Set the name attribute for backward compatibility.
     */
    public function setNameAttribute(string $value): void
    {
        Log::info('Using deprecated name attribute. Use title instead.', [
            'class' => self::class,
            'id' => $this->id ?? 'new',
        ]);
        $this->attributes['title'] = $value;
    }

    /**
     * Add a track to the playlist.
     */
    public function addTrack(Track $track, ?int $position = null): void
    {
        if ($position === null) {
            // Get the highest position and add 1
            $position = $this->tracks()->max('position') ?? -1;
            $position++;
        }

        $this->tracks()->attach($track->id, ['position' => $position]);
    }

    /**
     * Remove a track from the playlist.
     */
    public function removeTrack(Track $track): void
    {
        $this->tracks()->detach($track->id);
    }

    /**
     * Get tracks count
     */
    public function getTracksCountAttribute(): int
    {
        return (int) $this->tracks()->count();
    }
}
