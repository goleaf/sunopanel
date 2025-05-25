<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

final class YouTubeAccount extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'youtube_accounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'channel_id',
        'channel_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'account_info',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'token_expires_at' => 'datetime',
        'account_info' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the currently active YouTube account.
     */
    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Mark this account as active and deactivate all others.
     */
    public function markAsActive(): bool
    {
        try {
            // Deactivate all other accounts
            self::where('id', '!=', $this->id)->update(['is_active' => false]);
            
            // Activate this account
            $this->is_active = true;
            $result = $this->save();
            
            Log::info('YouTube account marked as active', [
                'account_id' => $this->id,
                'name' => $this->name,
                'channel_id' => $this->channel_id,
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to mark YouTube account as active', [
                'account_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if the access token is expired.
     */
    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    /**
     * Check if the account has valid tokens.
     */
    public function hasValidTokens(): bool
    {
        return !empty($this->access_token) && !$this->isTokenExpired();
    }

    /**
     * Get the channel URL.
     */
    public function getChannelUrlAttribute(): ?string
    {
        return $this->channel_id 
            ? "https://www.youtube.com/channel/{$this->channel_id}" 
            : null;
    }

    /**
     * Scope a query to only include active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include accounts with valid tokens.
     */
    public function scopeWithValidTokens($query)
    {
        return $query->whereNotNull('access_token')
                    ->where(function ($q) {
                        $q->whereNull('token_expires_at')
                          ->orWhere('token_expires_at', '>', now());
                    });
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Ensure only one account can be active at a time
        static::saving(function (YouTubeAccount $account) {
            if ($account->is_active && $account->isDirty('is_active')) {
                self::where('id', '!=', $account->id)->update(['is_active' => false]);
            }
        });
    }
}
