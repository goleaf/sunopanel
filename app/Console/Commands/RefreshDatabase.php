<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RefreshDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refresh-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the database and run the TrackSeeder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database refresh...');

        // Drop all tables and re-run migrations
        $this->info('Migrating database tables...');
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->info(Artisan::output());

        // Run the TrackSeeder
        $this->info('Seeding tracks...');
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\TrackSeeder',
            '--force' => true,
        ]);
        $this->info(Artisan::output());

        $this->info('Database refresh completed successfully!');

        return Command::SUCCESS;
    }
}
