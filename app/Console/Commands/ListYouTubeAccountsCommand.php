<?php

namespace App\Console\Commands;

use App\Models\YouTubeAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ListYouTubeAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:accounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all YouTube accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if the table exists
        if (!Schema::hasTable('youtube_accounts')) {
            $this->error('YouTube accounts table does not exist.');
            return 1;
        }
        
        // Get accounts directly from the database
        $accounts = DB::table('youtube_accounts')
            ->orderBy('is_active', 'desc')
            ->orderBy('last_used_at', 'desc')
            ->get();
        
        if ($accounts->isEmpty()) {
            $this->info('No YouTube accounts found. Use the web interface to add accounts.');
            return 0;
        }
        
        $this->info('YouTube Accounts:');
        
        $rows = [];
        foreach ($accounts as $account) {
            // Convert last_used_at to a readable format
            $lastUsed = $account->last_used_at 
                ? \Carbon\Carbon::parse($account->last_used_at)->diffForHumans() 
                : 'Never';
                
            $rows[] = [
                'ID' => $account->id,
                'Name' => $account->name,
                'Channel' => $account->channel_name,
                'Status' => $account->is_active ? '<fg=green>ACTIVE</>' : '',
                'Last Used' => $lastUsed,
                'Is Active' => $account->is_active ? 'Yes' : 'No',
            ];
        }
        
        $this->table(['ID', 'Name', 'Channel', 'Status', 'Last Used', 'Is Active'], $rows);
        
        return 0;
    }
} 