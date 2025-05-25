<?php

namespace App\Console\Commands;

use App\Models\YouTubeAccount;
use App\Services\YouTubeService;
use Illuminate\Console\Command;

class SetActiveYouTubeAccountCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:use-account {account_id : The ID of the account to use}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the active YouTube account';

    /**
     * The YouTube service.
     */
    protected YouTubeService $youtubeService;
    
    /**
     * Create a new command instance.
     */
    public function __construct(YouTubeService $youtubeService)
    {
        parent::__construct();
        $this->youtubeService = $youtubeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accountId = $this->argument('account_id');
        $account = YouTubeAccount::find($accountId);
        
        if (!$account) {
            $this->error("YouTube account with ID {$accountId} not found.");
            return 1;
        }
        
        if ($account->is_active) {
            $this->info("Account '{$account->name}' is already active.");
            return 0;
        }
        
        $success = $this->youtubeService->setAccount($account);
        
        if ($success) {
            $this->info("Successfully set '{$account->name}' as the active YouTube account.");
            return 0;
        } else {
            $this->error("Failed to set account as active. The token may have expired.");
            return 1;
        }
    }
} 