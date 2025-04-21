<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ServerRenderingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure SSR for Livewire 3
        config(['livewire.ssr.enabled' => true]);
        
        // Increase SSR cache duration for better performance
        config(['livewire.ssr.cache_timeout' => 60 * 60 * 24]); // 24 hours
        
        // Add 1 hour TTL
        config(['livewire.ssr.ttl' => 60 * 60]);
        
        // Enable SSR data callback for improved hydration
        config(['livewire.ssr.ssr_data_callback' => true]);
        
        // Set browser option for puppeteer
        config(['livewire.ssr.browser' => null]);
        
        // Comment out script route setting for testing
        // Livewire::setScriptRoute(function () {
        //     return route('livewire.js');
        // });
        
        // Set critical paths that should always be rendered on the server
        $criticalPaths = [
            'dashboard',
            'tracks.index',
            'genres.index',
            'playlists.index',
            'system.stats',
        ];
        
        foreach ($criticalPaths as $path) {
            if (request()->routeIs($path)) {
                config(['livewire.ssr.enabled' => true]);
                break;
            }
        }
    }
} 