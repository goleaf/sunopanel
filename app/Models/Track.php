<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Track extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'mp3_url',
        'image_url',
        'mp3_path',
        'image_path',
        'mp4_path',
        'status',
        'progress',
        'error_message'
    ];

    /**
     * Get the genres associated with this track.
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }
}
