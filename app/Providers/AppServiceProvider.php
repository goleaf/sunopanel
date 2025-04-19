<?php

namespace App\Providers;

use App\Http\Middleware\LoggingMiddleware;
use App\Services\Logging\LoggingService;
use App\Services\CacheService;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the LoggingService as a singleton
        $this->app->singleton(LoggingService::class, function ($app) {
            return new LoggingService;
        });

        // Register the CacheService as a singleton
        $this->app->singleton(CacheService::class, function ($app) {
            return new CacheService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the logging middleware
        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(LoggingMiddleware::class);

        // Register a null authentication driver
        Auth::viaRequest('null', function ($request) {
            return null; // Always return null to disable authentication
        });

        // Register components
        Blade::component('components.search', 'search');
        Blade::component('components.sorting', 'sorting');
        Blade::component('components.audio-player', 'audio-player');
        Blade::component('components.dashboard-widget', 'dashboard-widget');
        Blade::component('components.notification', 'notification');

        // Form components
        Blade::component('playlists.form', 'playlists-form');
        Blade::component('genres.form', 'genres-form');
        Blade::component('tracks.form', 'tracks-form');
    }
}
