<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
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
        'suno_id',
        'mp3_url',
        'image_url',
        'mp3_path',
        'image_path',
        'mp4_path',
        'genres_string',
        'status',
        'progress',
        'error_message',
        'youtube_video_id',
        'youtube_playlist_id',
        'youtube_uploaded_at',
        'youtube_views',
        'youtube_stats_updated_at',
        'youtube_enabled',
        // YouTube Analytics Fields
        'youtube_view_count',
        'youtube_like_count',
        'youtube_dislike_count',
        'youtube_comment_count',
        'youtube_favorite_count',
        'youtube_duration',
        'youtube_definition',
        'youtube_caption',
        'youtube_licensed_content',
        'youtube_privacy_status',
        'youtube_published_at',
        'youtube_analytics_updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'progress' => 'integer',
        'youtube_uploaded_at' => 'datetime',
        'youtube_stats_updated_at' => 'datetime',
        'youtube_views' => 'integer',
        'youtube_enabled' => 'boolean',
        // YouTube Analytics Casts
        'youtube_view_count' => 'integer',
        'youtube_like_count' => 'integer',
        'youtube_dislike_count' => 'integer',
        'youtube_comment_count' => 'integer',
        'youtube_favorite_count' => 'integer',
        'youtube_published_at' => 'datetime',
        'youtube_analytics_updated_at' => 'datetime',
    ];

    /**
     * Valid status values for tracks
     *
     * @var array<int, string>
     */
    public static array $statuses = [
        'pending',     // Waiting to be processed
        'processing',  // Currently being processed
        'completed',   // Successfully processed
        'failed',      // Processing failed
        'stopped',     // Processing manually stopped
    ];

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

    /**
     * Get the full file path for the MP3 file.
     */
    public function getMp3FilePathAttribute(): ?string
    {
        return $this->mp3_path ? storage_path('app/public/' . $this->mp3_path) : null;
    }

    /**
     * Get the full file path for the image file.
     */
    public function getImageFilePathAttribute(): ?string
    {
        return $this->image_path ? storage_path('app/public/' . $this->image_path) : null;
    }

    /**
     * Get the full file path for the MP4 file.
     */
    public function getMp4FilePathAttribute(): ?string
    {
        return $this->mp4_path ? storage_path('app/public/' . $this->mp4_path) : null;
    }
    
    /**
     * Get the YouTube video URL.
     */
    public function getYoutubeUrlAttribute(): ?string
    {
        return $this->youtube_video_id 
            ? "https://www.youtube.com/watch?v={$this->youtube_video_id}" 
            : null;
    }
    
    /**
     * Get the YouTube embed URL.
     */
    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        return $this->youtube_video_id 
            ? "https://www.youtube.com/embed/{$this->youtube_video_id}" 
            : null;
    }
    
    /**
     * Get the YouTube playlist URL.
     */
    public function getYoutubePlaylistUrlAttribute(): ?string
    {
        return $this->youtube_playlist_id 
            ? "https://www.youtube.com/playlist?list={$this->youtube_playlist_id}" 
            : null;
    }
    
    /**
     * Determine if the track has been uploaded to YouTube.
     */
    public function getIsUploadedToYoutubeAttribute(): bool
    {
        return $this->youtube_video_id !== null;
    }

    /**
     * Check if the track was uploaded to YouTube.
     */
    public function getYoutubeUploadedAttribute(): bool
    {
        return !empty($this->youtube_video_id);
    }

    /**
     * Get the YouTube video URL.
     */
    public function getYoutubeVideoUrlAttribute(): ?string
    {
        return $this->youtube_video_id 
            ? "https://www.youtube.com/watch?v={$this->youtube_video_id}" 
            : null;
    }

    /**
     * Toggle the YouTube enabled status for this track.
     */
    public function toggleYoutubeEnabled(): bool
    {
        $this->youtube_enabled = !$this->youtube_enabled;
        return $this->save();
    }

    /**
     * Scope a query to only include tracks with specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include completed tracks.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include tracks uploaded to YouTube.
     */
    public function scopeUploadedToYoutube($query)
    {
        return $query->whereNotNull('youtube_video_id');
    }

    /**
     * Scope a query to only include tracks not uploaded to YouTube.
     */
    public function scopeNotUploadedToYoutube($query)
    {
        return $query->whereNull('youtube_video_id');
    }

    /**
     * Scope a query to only include tracks with analytics data.
     */
    public function scopeWithAnalytics($query)
    {
        return $query->whereNotNull('youtube_analytics_updated_at');
    }

    /**
     * Scope a query to order by YouTube view count.
     */
    public function scopeOrderByViews($query, string $direction = 'desc')
    {
        return $query->orderBy('youtube_view_count', $direction);
    }

    /**
     * Scope a query to order by YouTube like count.
     */
    public function scopeOrderByLikes($query, string $direction = 'desc')
    {
        return $query->orderBy('youtube_like_count', $direction);
    }

    /**
     * Get formatted view count.
     */
    public function getFormattedViewCountAttribute(): string
    {
        if (!$this->youtube_view_count) {
            return '0';
        }

        if ($this->youtube_view_count >= 1000000) {
            return number_format($this->youtube_view_count / 1000000, 1) . 'M';
        }

        if ($this->youtube_view_count >= 1000) {
            return number_format($this->youtube_view_count / 1000, 1) . 'K';
        }

        return number_format($this->youtube_view_count);
    }

    /**
     * Get formatted like count.
     */
    public function getFormattedLikeCountAttribute(): string
    {
        if (!$this->youtube_like_count) {
            return '0';
        }

        if ($this->youtube_like_count >= 1000000) {
            return number_format($this->youtube_like_count / 1000000, 1) . 'M';
        }

        if ($this->youtube_like_count >= 1000) {
            return number_format($this->youtube_like_count / 1000, 1) . 'K';
        }

        return number_format($this->youtube_like_count);
    }

    /**
     * Check if analytics data is stale (older than 1 hour).
     */
    public function hasStaleAnalytics(): bool
    {
        if (!$this->youtube_analytics_updated_at) {
            return true;
        }

        return $this->youtube_analytics_updated_at->lt(now()->subHour());
    }

    /**
     * Get engagement rate (likes / views * 100).
     */
    public function getEngagementRateAttribute(): float
    {
        if (!$this->youtube_view_count || $this->youtube_view_count === 0) {
            return 0.0;
        }

        $totalEngagement = ($this->youtube_like_count ?? 0) + ($this->youtube_comment_count ?? 0);
        return round(($totalEngagement / $this->youtube_view_count) * 100, 2);
    }
}
