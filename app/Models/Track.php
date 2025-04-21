<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Track extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'mp3_url',
        'image_url',
        'mp3_path',
        'image_path',
        'mp4_path',
        'genres_string',
        'status',
        'progress',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'progress' => 'integer',
    ];

    /**
     * Valid status values for tracks
     *
     * @var array<int, string>
     */
    public static $statuses = [
        'pending',     // Waiting to be processed
        'processing',  // Currently being processed
        'completed',   // Successfully processed
        'failed',      // Processing failed
        'stopped',     // Processing manually stopped
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Track $track) {
            if (empty($track->slug)) {
                $track->slug = Str::slug($track->title);
            }
        });
    }

    /**
     * Get the genres that belong to this track.
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    /**
     * Get the list of genre names.
     */
    public function getGenresListAttribute(): string
    {
        return $this->genres->pluck('name')->implode(', ');
    }

    /**
     * Get the storage URL for the MP3 file.
     */
    public function getMp3StorageUrlAttribute(): ?string
    {
        return $this->mp3_path ? Storage::disk('public')->url($this->mp3_path) : null;
    }

    /**
     * Get the storage URL for the image file.
     */
    public function getImageStorageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /**
     * Get the storage URL for the MP4 file.
     */
    public function getMp4StorageUrlAttribute(): ?string
    {
        return $this->mp4_path ? Storage::disk('public')->url($this->mp4_path) : null;
    }
}
