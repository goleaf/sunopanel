<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\SimpleYouTubeUploader;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the SimpleYouTubeUploader service
        $this->app->singleton(SimpleYouTubeUploader::class, function ($app) {
            return new SimpleYouTubeUploader();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define feature flags
        Feature::define('youtube-bulk-upload', fn () => true);
        Feature::define('advanced-analytics', fn () => false);
        Feature::define('auto-genre-detection', fn () => true);
        Feature::define('video-thumbnails', fn () => true);
        Feature::define('playlist-management', fn () => false);
        Feature::define('real-time-updates', fn () => true);
    }
}
