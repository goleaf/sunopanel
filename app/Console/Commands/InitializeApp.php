<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InitializeApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:initialize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize the application: migrate, seed, link storage';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Initializing SunoPanel application...');

        $this->info('Migrating database...');
        $this->comment(Artisan::call('migrate:fresh', ['--force' => true]));
        
        $this->info('Linking storage...');
        $this->comment(Artisan::call('storage:link'));
        
        $this->info('Seeding the database with tracks...');
        $this->comment(Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\TrackSeeder', '--force' => true]));
        
        $this->info('Clearing cache...');
        $this->comment(Artisan::call('cache:clear'));
        $this->comment(Artisan::call('view:clear'));
        $this->comment(Artisan::call('config:clear'));
        
        $this->info('Creating storage directories...');
        if (!is_dir(storage_path('app/public/uploads'))) {
            mkdir(storage_path('app/public/uploads'), 0755, true);
        }
        
        $this->info('Application initialization complete!');
        $this->info('');
        $this->info('You can now access the application at the configured URL.');
        $this->info('Dashboard: /dashboard');
        $this->info('Tracks: /tracks');
        $this->info('Genres: /genres');
        $this->info('Playlists: /playlists');
        $this->info('Batch Operations: /batch');
        
        return Command::SUCCESS;
    }
} 