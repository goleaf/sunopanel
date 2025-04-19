<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BatchOperation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'status',
        'details',
        'processed_items',
        'failed_items',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'details' => 'json',
        'processed_items' => 'integer',
        'failed_items' => 'integer',
    ];

    /**
     * Get the user that owns the batch operation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
