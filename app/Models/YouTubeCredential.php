<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YouTubeCredential extends Model
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
     * @var array
     */
    protected $fillable = [
        'access_token',
        'refresh_token',
        'token_created_at',
        'token_expires_in',
        'client_id',
        'client_secret',
        'redirect_uri',
        'use_oauth',
        'user_email',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'token_created_at' => 'integer',
        'token_expires_in' => 'integer',
        'use_oauth' => 'boolean',
    ];

    /**
     * Get the most recent YouTube credential
     *
     * @return self|null
     */
    public static function getLatest(): ?self
    {
        return self::latest()->first();
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
     * Determine if this credential has valid auth data
     * 
     * @return bool
     */
    public function hasValidAuthData(): bool
    {
        if ($this->use_oauth) {
            return !empty($this->client_id) && !empty($this->client_secret) && !empty($this->redirect_uri);
        }
        
        return !empty($this->user_email);
    }
}
