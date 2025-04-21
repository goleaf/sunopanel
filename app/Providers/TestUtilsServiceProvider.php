<?php

declare(strict_types=1);

namespace App\Providers;

use App\Console\Commands\AddTestTypeHintsCommand;
use App\Console\Commands\CleanupSkippedTestsCommand;
use App\Console\Commands\ConvertTestDocblocksCommand;
use App\Console\Commands\StandardizeTestsCommand;
use App\Console\Commands\ConvertLivewireTestsCommand;
use App\Console\Commands\FixRemainingTestsCommand;
use Illuminate\Support\ServiceProvider;

final class TestUtilsServiceProvider extends ServiceProvider
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
        if ($this->app->runningInConsole()) {
            $this->commands([
                ConvertTestDocblocksCommand::class,
                AddTestTypeHintsCommand::class,
                StandardizeTestsCommand::class,
                CleanupSkippedTestsCommand::class,
                ConvertLivewireTestsCommand::class,
                FixRemainingTestsCommand::class,
            ]);
        }
    }
} 