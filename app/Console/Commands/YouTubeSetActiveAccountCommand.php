<?php

namespace App\Console\Commands;

use App\Models\YouTubeAccount;
use App\Services\YouTubeService;
use Illuminate\Console\Command;

class YouTubeSetActiveAccountCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:use-account {account_id : ID of the YouTube account to use}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the active YouTube account for uploads';

    /**
     * The YouTube service instance.
     *
     * @var \App\Services\YouTubeService
     */
    protected $youtubeService;

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
            $this->info('Run "php artisan youtube:accounts" to see available accounts.');
            return 1;
        }
        
        // Set the account as active
        $success = $this->youtubeService->setAccount($account);
        
        if ($success) {
            $this->info("Successfully set {$account->getDisplayName()} as the active YouTube account.");
            return 0;
        } else {
            $this->error("Failed to set account as active. The account may have expired credentials.");
            return 1;
        }
    }
} 