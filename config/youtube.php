<?php

return [
    // API credentials (can be left empty if using simple uploader)
    'api_key' => env('YOUTUBE_API_KEY'),
    'client_id' => env('YOUTUBE_CLIENT_ID'),
    'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
    'redirect_uri' => env('YOUTUBE_REDIRECT_URI'),
    'application_name' => env('YOUTUBE_APPLICATION_NAME', 'SunoPanel'),
    'scopes' => explode(' ', env('YOUTUBE_SCOPES', 'https://www.googleapis.com/auth/youtube https://www.googleapis.com/auth/youtube.upload')),
    'access_token' => env('YOUTUBE_ACCESS_TOKEN'),
    'refresh_token' => env('YOUTUBE_REFRESH_TOKEN'),
    'token_expires_at' => env('YOUTUBE_TOKEN_EXPIRES_AT'),
    
    // Simple uploader credentials
    'email' => env('YOUTUBE_EMAIL'),
    'password' => env('YOUTUBE_PASSWORD'),
    
    // Default settings
    'default_category_id' => env('YOUTUBE_DEFAULT_CATEGORY_ID', 10), // 10 is Music
    'default_privacy_status' => env('YOUTUBE_DEFAULT_PRIVACY_STATUS', 'unlisted'),
    
    // Use simple uploader (username/password) instead of API
    'use_simple_uploader' => env('YOUTUBE_USE_SIMPLE_UPLOADER', true),
]; 