<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'genre_id',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Genre $genre) {
            if (empty($genre->slug)) {
                $genre->slug = Str::slug($genre->name);
            }
        });
    }

    /**
     * Get the tracks that belong to this genre.
     */
    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class);
    }

    /**
     * Scope a query to only include genres with tracks.
     */
    public function scopeWithTracks($query)
    {
        return $query->has('tracks');
    }

    /**
     * Scope a query to order genres by name.
     */
    public function scopeOrderByName($query)
    {
        return $query->orderBy('name');
    }
}
