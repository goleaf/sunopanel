<?php

namespace App\Providers;

use App\Http\Middleware\ErrorLoggingMiddleware;
use App\Services\Logging\ErrorLogService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Http\Kernel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the ErrorLogService as a singleton
        $this->app->singleton(ErrorLogService::class, function ($app) {
            return new ErrorLogService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the error logging middleware
        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(ErrorLoggingMiddleware::class);
        
        // Register a null authentication driver
        Auth::viaRequest('null', function ($request) {
            return null; // Always return null to disable authentication
        });

        // Register components
        Blade::component('components.search', 'search');
        Blade::component('components.sorting', 'sorting');
        Blade::component('components.audio-player', 'audio-player');
        
        // Form components
        Blade::component('playlists.form', 'playlists-form');
        Blade::component('genres.form', 'genres-form');
        Blade::component('tracks.form', 'tracks-form');
    }
}
