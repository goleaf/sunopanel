<?php

namespace App\Providers;

use App\Exceptions\LivewireExceptionHandler;
use Illuminate\Support\ServiceProvider;
use Livewire\LivewireServiceProvider;
use Livewire\Component;
use Livewire\Livewire;

class LivewireOptimizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register our custom exception handler
        $this->app->singleton('livewire.exception-handler', function () {
            return new LivewireExceptionHandler();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // In Livewire 3, hooks are handled differently
        // We'll comment out the old hooks for now
        
        /*
        // Set default values for all Livewire components
        Livewire::hook('component.booted', function(Component $component) {
            // Automatically enable SSR for components
            if (!isset($component->shouldRenderOnServer)) {
                $component->shouldRenderOnServer = true;
            }

            // Global hook for caching component data when appropriate
            if (isset($component->shouldCacheRender) && $component->shouldCacheRender) {
                $ttl = $component->renderCacheTtl ?? 60; // Default to 60 seconds
                
                // Add caching to the component's render method
                $view = $component->render();
                
                // Store the component in cache if it implements a cache key
                if (method_exists($component, 'getCacheKey')) {
                    $cacheKey = $component->getCacheKey();
                    
                    if (!cache()->has($cacheKey)) {
                        cache()->put($cacheKey, $view, $ttl);
                    }
                }
            }
        });
        
        // Use our custom exception handler
        Livewire::setExceptionHandler('livewire.exception-handler');
        
        // Add optimized asset loading for Livewire
        Livewire::setScriptRoute(function ($handle) {
            return route('livewire.js', ['v' => LIVEWIRE_ASSET_VERSION]);
        });
        
        // Add deferred loading for non-critical components
        Livewire::hook('request.handle', function ($request, $next) {
            $response = $next($request);
            
            // Add browser hints for preloading critical assets
            if (method_exists($response, 'header')) {
                $response->header('Link', '</livewire.js>; rel=preload; as=script', false);
                $response->header('Link', '</css/app.css>; rel=preload; as=style', false);
            }
            
            return $response;
        });
        */
        
        // For Livewire 3, we'll add equivalent functionality when needed
        // Using the new Livewire::listen() API
    }
} 