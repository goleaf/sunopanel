<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'service',
        'payload',
        'ip_address',
        'user_agent',
        'received_at',
        'processed_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Scope to filter by service.
     */
    public function scopeForService($query, string $service)
    {
        return $query->where('service', $service);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get recent webhooks.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('received_at', '>=', now()->subHours($hours));
    }
} 