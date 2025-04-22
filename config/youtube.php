<?php

return [
    // Direct YouTube upload credentials - Required!
    'email' => env('YOUTUBE_EMAIL'),
    'password' => env('YOUTUBE_PASSWORD'),
    
    // Default settings for uploads
    'default_category_id' => env('YOUTUBE_DEFAULT_CATEGORY_ID', 10), // 10 is Music
    'default_privacy_status' => env('YOUTUBE_DEFAULT_PRIVACY_STATUS', 'unlisted'),
    
    // Selenium browser settings
    'browser' => env('YOUTUBE_BROWSER', 'chrome'), // chrome or firefox
    'headless' => env('YOUTUBE_HEADLESS', true),   // true for server environments
    
    // YouTube upload script paths
    'upload_script' => env('YOUTUBE_UPLOAD_SCRIPT', '/usr/local/bin/youtube-direct-upload'),
    
    // Upload timeouts (seconds)
    'process_timeout' => env('YOUTUBE_PROCESS_TIMEOUT', 3600),  // 1 hour
    'idle_timeout' => env('YOUTUBE_IDLE_TIMEOUT', 600),         // 10 minutes
]; 