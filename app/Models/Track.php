<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
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
        'mp3_url',
        'image_url',
        'mp3_path',
        'image_path',
        'mp4_path',
        'status',
        'progress',
        'error_message',
        'genres_string',
        'slug',
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
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Track $track) {
            if (empty($track->slug)) {
                $track->slug = Str::slug($track->title);
            }
            
            if (empty($track->status)) {
                $track->status = 'pending';
            }
            
            if ($track->progress === null) {
                $track->progress = 0;
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

    /**
     * Get a comma-separated list of genre names.
     */
    public function getGenresListAttribute(): string
    {
        return $this->genres->pluck('name')->implode(', ');
    }

    /**
     * Parse track information from a line of text.
     *
     * @param string $line
     * @return array
     */
    public static function parseFromLine(string $line): array
    {
        $parts = explode('|', $line);
        
        if (count($parts) < 3) {
            throw new \InvalidArgumentException('Invalid track format: ' . $line);
        }
        
        $fileName = trim($parts[0]);
        $mp3Url = trim($parts[1]);
        $imageUrl = trim($parts[2]);
        $genresString = isset($parts[3]) ? trim($parts[3]) : '';
        
        // Clean up title (remove .mp3 extension)
        $title = str_replace('.mp3', '', $fileName);
        
        return [
            'title' => $title,
            'mp3_url' => $mp3Url,
            'image_url' => $imageUrl,
            'genres_string' => $genresString,
            'slug' => Str::slug($title),
            'status' => 'pending',
            'progress' => 0,
        ];
    }

    /**
     * Parse multiple tracks from a text input.
     *
     * @param string $text
     * @return Collection
     */
    public static function parseFromText(string $text): Collection
    {
        $lines = explode("\n", $text);
        return collect($lines)
            ->filter(fn($line) => !empty(trim($line)))
            ->map(fn($line) => self::parseFromLine($line));
    }
}
