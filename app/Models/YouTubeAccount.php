<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YouTubeAccount extends Model
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
        'last_used_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'account_info' => 'array',
    ];

    /**
     * Check if the account's token is expired
     *
     * @return bool
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return true;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Mark this account as the active account
     *
     * @return bool
     */
    public function markAsActive(): bool
    {
        // First deactivate all accounts
        try {
            \Illuminate\Support\Facades\Log::info('Deactivating all YouTube accounts');
            self::query()->update(['is_active' => false]);
            
            // Then activate this one
            \Illuminate\Support\Facades\Log::info('Activating account', ['id' => $this->id, 'name' => $this->name]);
            $this->is_active = true;
            $this->last_used_at = now();
            $saved = $this->save();
            
            \Illuminate\Support\Facades\Log::info('Account activation result', [
                'id' => $this->id, 
                'name' => $this->name,
                'is_active' => $this->is_active,
                'saved' => $saved,
                'last_used_at' => $this->last_used_at
            ]);
            
            return $saved;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to mark account as active', [
                'id' => $this->id,
                'name' => $this->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get the active account
     *
     * @return self|null
     */
    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Get the human-readable display name for this account
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        if ($this->name) {
            return $this->name;
        }
        
        if ($this->channel_name) {
            return $this->channel_name;
        }
        
        if ($this->email) {
            return $this->email;
        }
        
        return 'YouTube Account #' . $this->id;
    }

    /**
     * Flush the cache for this model.
     * 
     * @return void
     */
    public function flushCache(): void
    {
        // No actual cache to flush, but useful for forcing model refreshes
    }
}
