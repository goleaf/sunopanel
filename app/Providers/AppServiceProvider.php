<?php

namespace App\Providers;

use App\Services\SimpleYouTubeUploader;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        //
    }
}
