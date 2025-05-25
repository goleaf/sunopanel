<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Schedule random YouTube upload once per day
        $schedule->command('youtube:upload-random')->daily();
        
        // Schedule YouTube analytics updates
        $schedule->command('youtube:update-analytics --limit=100')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground();
        
        // Schedule full analytics refresh twice daily
        $schedule->command('youtube:update-analytics --force --limit=500')
                 ->twiceDaily(6, 18)
                 ->withoutOverlapping()
                 ->runInBackground();
        
        // You can uncomment one of these for more frequent uploads
        // $schedule->command('youtube:upload-random')->hourly();
        // $schedule->command('youtube:upload-random')->everyFourHours();
        // $schedule->command('youtube:upload-random')->twiceDaily(8, 20);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 