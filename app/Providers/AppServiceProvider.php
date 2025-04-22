<?php

namespace App\Providers;

use App\Services\SimpleYouTubeUploader;
use App\Services\YouTubeUploader;
use App\Services\YouTubePlaylistManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the YouTubeUploader service
        $this->app->singleton(YouTubeUploader::class, function ($app) {
            return new YouTubeUploader();
        });
        
        // Register the YouTube playlist manager
        $this->app->singleton(YouTubePlaylistManager::class, function ($app) {
            return new YouTubePlaylistManager(
                $app->make(YouTubeUploader::class)
            );
        });
        
        // Register the SimpleYouTubeUploader service with dependency injection
        $this->app->singleton(SimpleYouTubeUploader::class, function ($app) {
            return new SimpleYouTubeUploader(
                $app->make(YouTubeUploader::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
