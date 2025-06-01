<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class YouTubeCredential extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'youtube_credentials';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'client_secret',
        'redirect_uri',
        'access_token',
        'refresh_token',
        'token_created_at',
        'use_oauth',
        'api_key',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'token_created_at' => 'integer',
        'use_oauth' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'client_secret',
        'access_token',
        'refresh_token',
        'api_key',
    ];

    /**
     * Get the most recent YouTube credential
     *
     * @return self|null
     */
    public static function getLatest(): ?self
    {
        return self::orderBy('created_at', 'desc')->first();
    }

    /**
     * Check if the token is expired
     *
     * @return bool
     */
    public function isTokenExpired(): bool
    {
        if (empty($this->token_created_at) || empty($this->token_expires_in)) {
            return true;
        }

        $expirationTime = $this->token_created_at + $this->token_expires_in;
        return time() >= $expirationTime;
    }

    /**
     * Get the latest credential record.
     */
    public static function latest(): ?self
    {
        return self::orderBy('created_at', 'desc')->first();
    }

    /**
     * Check if OAuth credentials are configured.
     */
    public function hasOAuthCredentials(): bool
    {
        return !empty($this->client_id) && !empty($this->client_secret);
    }

    /**
     * Check if API key is configured.
     */
    public function hasApiKey(): bool
    {
        return !empty($this->api_key);
    }

    /**
     * Check if valid authentication data exists.
     */
    public function hasValidAuthData(): bool
    {
        return $this->use_oauth ? $this->hasOAuthCredentials() : $this->hasApiKey();
    }

    /**
     * Check if access token exists.
     */
    public function hasAccessToken(): bool
    {
        return !empty($this->access_token);
    }

    /**
     * Check if refresh token exists.
     */
    public function hasRefreshToken(): bool
    {
        return !empty($this->refresh_token);
    }

    /**
     * Get the authentication method being used.
     */
    public function getAuthMethodAttribute(): string
    {
        return $this->use_oauth ? 'OAuth' : 'API Key';
    }

    /**
     * Scope a query to only include OAuth credentials.
     */
    public function scopeOAuth($query)
    {
        return $query->where('use_oauth', true);
    }

    /**
     * Scope a query to only include API key credentials.
     */
    public function scopeApiKey($query)
    {
        return $query->where('use_oauth', false);
    }
}
