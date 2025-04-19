<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Logging\LoggingService;
use Illuminate\Support\ServiceProvider;

final class LoggingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(LoggingService::class, function ($app) {
            return new LoggingService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
} 