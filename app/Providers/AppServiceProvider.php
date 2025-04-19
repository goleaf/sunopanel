<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
