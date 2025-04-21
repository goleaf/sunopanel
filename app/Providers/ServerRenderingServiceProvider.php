<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ServerRenderingServiceProvider extends ServiceProvider
{
    /**
     * Critical assets that should be preloaded for better performance.
     */
    protected array $preloadAssets = [
        '/vendor/livewire/livewire.js' => 'script',
        '/build/assets/app.css' => 'style',
        '/fonts/figtree-latin-400-normal.woff2' => 'font',
        '/fonts/figtree-latin-600-normal.woff2' => 'font',
    ];

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
        // Disable SSR for testing environment
        if ($this->app->environment('testing')) {
            config(['livewire.ssr.enabled' => false]);
            return;
        }
        
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
        
        // Add middleware to handle preloading critical assets
        $this->app->booted(function () {
            // Add middleware to the web group to add preload headers
            $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
            $kernel->prependToMiddlewarePriority(\App\Http\Middleware\AddPreloadHeaders::class);
            
            // Register the middleware in the web group
            $router = $this->app['router'];
            $router->pushMiddlewareToGroup('web', \App\Http\Middleware\AddPreloadHeaders::class);
        });
    }
} 