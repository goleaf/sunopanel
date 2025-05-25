<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\YouTubeAccount;
use App\Services\YouTubeService;
use Illuminate\Console\Command;

final class CheckYouTubeStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:check-status {--account-id= : Specific YouTube account ID to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check YouTube account status and upload capabilities';

    public function __construct(
        private readonly YouTubeService $youtubeService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $accountId = $this->option('account-id');

        if ($accountId) {
            $account = YouTubeAccount::find($accountId);
            if (!$account) {
                $this->error("YouTube account with ID {$accountId} not found.");
                return 1;
            }
            $accounts = collect([$account]);
        } else {
            $accounts = YouTubeAccount::all();
        }

        if ($accounts->isEmpty()) {
            $this->warn('No YouTube accounts found.');
            return 0;
        }

        $this->info('Checking YouTube account status...');
        $this->newLine();

        foreach ($accounts as $account) {
            $this->checkAccount($account);
            $this->newLine();
        }

        return 0;
    }

    private function checkAccount(YouTubeAccount $account): void
    {
        $this->info("Account: {$account->name} (ID: {$account->id})");
        $this->line("Channel ID: " . ($account->channel_id ?? 'Not set'));
        $this->line("Channel Title: " . ($account->channel_title ?? 'Not set'));
        $this->line("Active: " . ($account->is_active ? 'Yes' : 'No'));
        $this->line("Last Used: " . ($account->last_used_at ? $account->last_used_at->diffForHumans() : 'Never'));

        try {
            // Set the account for the service
            if (!$this->youtubeService->setAccount($account)) {
                $this->error("Failed to set YouTube account");
                return;
            }

            // Check authentication
            if (!$this->youtubeService->isAuthenticated()) {
                $this->error("❌ Not authenticated");
                return;
            }

            $this->info("✅ Authenticated");

            // Check account status
            $status = $this->youtubeService->checkAccountStatus();

            if ($status['can_upload']) {
                $this->info("✅ Can upload videos");
                $this->line("Channel: " . ($status['channel_title'] ?? 'Unknown'));
                $this->line("Channel ID: " . ($status['channel_id'] ?? 'Unknown'));
            } else {
                $this->error("❌ Cannot upload videos");
                $this->line("Reason: " . $status['reason']);
                $this->line("Error Type: " . $status['error_type']);
                
                if (isset($status['original_error'])) {
                    $this->line("Original Error: " . $status['original_error']);
                }
            }

            // Try to get channel info
            try {
                $channelInfo = $this->youtubeService->getChannelInfo();
                if (!empty($channelInfo)) {
                    $this->info("Channel Info Retrieved:");
                    $this->line("  Title: " . ($channelInfo['title'] ?? 'Unknown'));
                    $this->line("  Subscriber Count: " . ($channelInfo['subscriber_count'] ?? 'Unknown'));
                    $this->line("  Video Count: " . ($channelInfo['video_count'] ?? 'Unknown'));
                }
            } catch (\Exception $e) {
                $this->warn("Could not retrieve channel info: " . $e->getMessage());
            }

        } catch (\Exception $e) {
            $this->error("❌ Error checking account: " . $e->getMessage());
        }
    }
} 