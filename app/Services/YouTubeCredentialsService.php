<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

final class YouTubeCredentialsService
{
    /**
     * Check if OAuth credentials are available in the .env file
     *
     * @return bool
     */
    public function hasOAuthCredentials(): bool
    {
        return !empty(env('YOUTUBE_CLIENT_ID')) &&
               !empty(env('YOUTUBE_CLIENT_SECRET')) &&
               !empty(env('YOUTUBE_ACCESS_TOKEN')) &&
               !empty(env('YOUTUBE_REFRESH_TOKEN'));
    }

    /**
     * Check if simple authentication credentials are available in the .env file
     *
     * @return bool
     */
    public function hasSimpleCredentials(): bool
    {
        return !empty(env('YOUTUBE_EMAIL')) &&
               !empty(env('YOUTUBE_PASSWORD'));
    }

    /**
     * Check if any authentication credentials are available
     *
     * @return bool
     */
    public function hasCredentials(): bool
    {
        return $this->hasOAuthCredentials() || $this->hasSimpleCredentials();
    }

    /**
     * Update the YouTube API configuration with values from .env
     * 
     * @return void
     */
    public function updateConfig(): void
    {
        // OAuth credentials
        if ($this->hasOAuthCredentials()) {
            Config::set('youtube.client_id', env('YOUTUBE_CLIENT_ID'));
            Config::set('youtube.client_secret', env('YOUTUBE_CLIENT_SECRET'));
            Config::set('youtube.access_token', env('YOUTUBE_ACCESS_TOKEN'));
            Config::set('youtube.refresh_token', env('YOUTUBE_REFRESH_TOKEN'));
        }
        
        // Simple authentication credentials
        if ($this->hasSimpleCredentials()) {
            Config::set('youtube.email', env('YOUTUBE_EMAIL'));
            Config::set('youtube.password', env('YOUTUBE_PASSWORD'));
        }
    }

    /**
     * Load YouTube credentials and determine which authentication method to use
     *
     * @return array
     */
    public function loadCredentials(): array
    {
        $this->updateConfig();
        
        $useOAuth = $this->hasOAuthCredentials();
        
        return [
            'use_oauth' => $useOAuth,
            'has_simple_auth' => $this->hasSimpleCredentials(),
            'credentials_available' => $this->hasCredentials(),
            'config' => [
                'client_id' => env('YOUTUBE_CLIENT_ID'),
                'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
                'email' => env('YOUTUBE_EMAIL'),
                'auth_method' => $useOAuth ? 'oauth' : 'simple'
            ]
        ];
    }
} 