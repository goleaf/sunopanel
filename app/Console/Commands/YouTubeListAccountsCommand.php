<?php

namespace App\Console\Commands;

use App\Models\YouTubeAccount;
use Illuminate\Console\Command;

class YouTubeListAccountsCommand extends Command
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
    protected $description = 'List all YouTube accounts configured in the system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accounts = YouTubeAccount::all();
        
        if ($accounts->isEmpty()) {
            $this->info('No YouTube accounts found. Add an account through the web interface.');
            return 0;
        }
        
        $activeAccount = YouTubeAccount::getActive();
        
        $this->info('YouTube Accounts:');
        $this->table(
            ['ID', 'Name', 'Channel', 'Email', 'Active', 'Token Expires', 'Last Used'],
            $accounts->map(function ($account) use ($activeAccount) {
                return [
                    'id' => $account->id,
                    'name' => $account->getDisplayName(),
                    'channel' => $account->channel_name ?? 'Unknown',
                    'email' => $account->email ?? 'Unknown',
                    'active' => $account->is_active ? 'Yes' : 'No',
                    'expires' => $account->token_expires_at 
                        ? ($account->isTokenExpired() ? 'Expired' : $account->token_expires_at->diffForHumans()) 
                        : 'Unknown',
                    'last_used' => $account->last_used_at 
                        ? $account->last_used_at->diffForHumans() 
                        : 'Never',
                ];
            })
        );
        
        if ($activeAccount) {
            $this->info("Current active account: {$activeAccount->getDisplayName()} (ID: {$activeAccount->id})");
        } else {
            $this->warn('No active YouTube account set. Use "youtube:use-account <id>" to set an active account.');
        }
        
        return 0;
    }
} 