<?php

namespace App\Providers;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class QueueServiceProvider extends ServiceProvider
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
        // Set default queue connection to database
        config(['queue.default' => 'database']);

        Queue::before(function (JobProcessing $event) {
            // Log when jobs start processing
            Log::debug('Processing job', [
                'id' => $event->job->getJobId(),
                'name' => $event->job->resolveName(),
                'connection' => $event->connectionName,
            ]);
        });

        Queue::after(function (JobProcessed $event) {
            // Log when jobs finish processing
            Log::debug('Job processed', [
                'id' => $event->job->getJobId(),
                'name' => $event->job->resolveName(),
                'connection' => $event->connectionName,
            ]);
        });

        Queue::failing(function (JobFailed $event) {
            // Log when jobs fail
            Log::error('Job failed', [
                'id' => $event->job->getJobId(),
                'name' => $event->job->resolveName(),
                'connection' => $event->connectionName,
                'exception' => $event->exception->getMessage(),
            ]);
        });
    }
} 