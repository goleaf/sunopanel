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