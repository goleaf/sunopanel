<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Logging\ErrorLogService;
use Illuminate\Support\ServiceProvider;

final class LoggingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ErrorLogService::class, function ($app) {
            return new ErrorLogService();
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