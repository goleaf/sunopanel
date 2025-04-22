<?php

namespace App\Providers;

use App\Services\YouTubeUploaderScripts;
use Google_Client;
use Google_Service_YouTube;
use Illuminate\Support\ServiceProvider;

class YouTubeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('google-client', function () {
            $client = new Google_Client();
            $client->setApplicationName(config('youtube.application_name'));
            $client->setClientId(config('youtube.client_id'));
            $client->setClientSecret(config('youtube.client_secret'));
            $client->setRedirectUri(config('youtube.redirect_uri'));
            $client->setScopes(config('youtube.scopes'));
            $client->setAccessType('offline');
            $client->setPrompt('consent');
            $client->setApprovalPrompt('force');
            
            // Set tokens if they exist
            if (config('youtube.access_token')) {
                $client->setAccessToken([
                    'access_token' => config('youtube.access_token'),
                    'refresh_token' => config('youtube.refresh_token'),
                    'expires_in' => config('youtube.token_expires_at'),
                ]);
                
                if ($client->isAccessTokenExpired()) {
                    if ($client->getRefreshToken()) {
                        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                        $this->updateTokensInEnv($client);
                    }
                }
            }
            
            return $client;
        });
        
        $this->app->singleton('youtube', function ($app) {
            $client = $app->make('google-client');
            return new Google_Service_YouTube($client);
        });
    }
    
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/youtube.php' => config_path('youtube.php'),
        ], 'config');
        
        // Install YouTube uploader scripts
        if ($this->app->environment() !== 'testing') {
            YouTubeUploaderScripts::install();
        }
    }
    
    /**
     * Update tokens in .env file
     */
    private function updateTokensInEnv(Google_Client $client): void
    {
        if (!$client->getAccessToken()) {
            return;
        }
        
        $token = $client->getAccessToken();
        
        // Update the .env file
        $path = base_path('.env');
        if (file_exists($path)) {
            $env = file_get_contents($path);
            
            $env = preg_replace(
                '/^YOUTUBE_ACCESS_TOKEN=.*$/m',
                'YOUTUBE_ACCESS_TOKEN=' . $token['access_token'],
                $env
            );
            
            if (isset($token['refresh_token'])) {
                $env = preg_replace(
                    '/^YOUTUBE_REFRESH_TOKEN=.*$/m',
                    'YOUTUBE_REFRESH_TOKEN=' . $token['refresh_token'],
                    $env
                );
            }
            
            $expiresAt = time() + $token['expires_in'];
            $env = preg_replace(
                '/^YOUTUBE_TOKEN_EXPIRES_AT=.*$/m',
                'YOUTUBE_TOKEN_EXPIRES_AT=' . $expiresAt,
                $env
            );
            
            file_put_contents($path, $env);
        }
    }
} 