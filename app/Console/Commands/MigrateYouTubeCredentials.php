<?php

namespace App\Console\Commands;

use App\Models\YouTubeCredential;
use Illuminate\Console\Command;

class MigrateYouTubeCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:migrate-credentials';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate YouTube credentials from .env to the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Migrating YouTube credentials to database...');

        // Check if we already have credentials in the database
        $existingCredential = YouTubeCredential::first();
        
        if ($existingCredential) {
            if (!$this->confirm('Credentials already exist in the database. Overwrite?', false)) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }
        
        // Get credentials from environment
        $clientId = env('YOUTUBE_CLIENT_ID');
        $clientSecret = env('YOUTUBE_CLIENT_SECRET');
        $redirectUri = env('YOUTUBE_REDIRECT_URI', 'https://sunopanel.prus.dev/youtube-auth');
        $accessToken = env('YOUTUBE_ACCESS_TOKEN');
        $refreshToken = env('YOUTUBE_REFRESH_TOKEN');
        $tokenCreatedAt = (int)env('YOUTUBE_TOKEN_CREATED_AT');
        $tokenExpiresIn = (int)env('YOUTUBE_TOKEN_EXPIRES_IN', 3600);
        $useOAuth = env('YOUTUBE_USE_OAUTH', false);
        $userEmail = env('YOUTUBE_EMAIL');
        
        // Check if we have enough data to proceed
        if (empty($clientId) && empty($userEmail)) {
            $this->error('No YouTube credentials found in .env file.');
            return 1;
        }
        
        // Create or update credentials in the database
        $credential = $existingCredential ?? new YouTubeCredential();
        
        $credential->client_id = $clientId;
        $credential->client_secret = $clientSecret;
        $credential->redirect_uri = $redirectUri;
        $credential->access_token = $accessToken;
        $credential->refresh_token = $refreshToken;
        $credential->token_created_at = $tokenCreatedAt ?: time();
        $credential->token_expires_in = $tokenExpiresIn;
        $credential->use_oauth = (bool)$useOAuth;
        $credential->user_email = $userEmail;
        
        $credential->save();
        
        $this->info('YouTube credentials migrated successfully!');
        $this->info('OAuth Status: ' . ($credential->use_oauth ? 'Enabled' : 'Disabled'));
        $this->info('Auth Type: ' . ($credential->use_oauth ? 'OAuth' : 'Email'));
        
        return 0;
    }
}
