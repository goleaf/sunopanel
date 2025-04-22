<?php

return [
    // Authentication Method Flags
    'use_oauth' => env('YOUTUBE_USE_OAUTH', false),
    'use_simple' => env('YOUTUBE_USE_SIMPLE', true),
    
    // OAuth Credentials - Required for YouTube API authentication
    'client_id' => env('YOUTUBE_CLIENT_ID'),
    'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
    'access_token' => env('YOUTUBE_ACCESS_TOKEN'),
    'refresh_token' => env('YOUTUBE_REFRESH_TOKEN'),
    'token_created_at' => env('YOUTUBE_TOKEN_CREATED_AT'),
    'token_expires_in' => env('YOUTUBE_TOKEN_EXPIRES_IN', 3600),
    
    // Simple Uploader Credentials - Required for browser-based authentication
    'email' => env('YOUTUBE_EMAIL'),
    'password' => env('YOUTUBE_PASSWORD'),
    
    // Default settings for uploads
    'default_category_id' => env('YOUTUBE_DEFAULT_CATEGORY_ID', 10), // 10 is Music
    'default_privacy_status' => env('YOUTUBE_DEFAULT_PRIVACY_STATUS', 'unlisted'),
    
    // Selenium browser settings (for simple uploader)
    'browser' => env('YOUTUBE_BROWSER', 'chrome'), // chrome or firefox
    'headless' => env('YOUTUBE_HEADLESS', true),   // true for server environments
    
    // YouTube upload script paths (for simple uploader)
    'upload_script' => env('YOUTUBE_UPLOAD_SCRIPT', '/usr/local/bin/youtube-direct-upload'),
    
    // Upload timeouts (seconds)
    'process_timeout' => env('YOUTUBE_PROCESS_TIMEOUT', 3600),  // 1 hour
    'idle_timeout' => env('YOUTUBE_IDLE_TIMEOUT', 600),         // 10 minutes
    
    // API settings
    'api_key' => env('YOUTUBE_API_KEY'),
    'api_chunk_size' => env('YOUTUBE_API_CHUNK_SIZE', 1 * 1024 * 1024), // 1MB chunks by default
]; 