<?php

namespace App\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Create a mock user that implements Authenticatable
        $this->app->singleton('mock-user', function () {
            return new class implements Authenticatable {
                public function getAuthIdentifierName()
                {
                    return 'id';
                }

                public function getAuthIdentifier()
                {
                    return 1;
                }

                public function getAuthPassword()
                {
                    return 'password';
                }

                public function getRememberToken()
                {
                    return null;
                }

                public function setRememberToken($value)
                {
                    // Do nothing
                }

                public function getRememberTokenName()
                {
                    return 'remember_token';
                }

                public function __get($key)
                {
                    return $key === 'id' ? 1 : null;
                }
            };
        });

        // Mock Auth class for systems without authentication
        $this->app->singleton('auth', function ($app) {
            return new class implements Guard {
                private $user = null;

                public function user()
                {
                    if ($this->user === null) {
                        $this->user = app('mock-user');
                    }
                    return null; // Return null for mock behavior
                }
                
                public function id()
                {
                    return null;
                }
                
                public function check()
                {
                    return false;
                }
                
                public function guest()
                {
                    return true;
                }
                
                public function extend($driver, $callback)
                {
                    return $this;
                }
                
                public function guard($name = null)
                {
                    return $this;
                }

                // Additional methods required by Guard interface
                public function validate(array $credentials = [])
                {
                    return false;
                }

                public function setUser(?Authenticatable $user)
                {
                    $this->user = $user;
                    return $this;
                }

                public function hasUser()
                {
                    return false;
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
