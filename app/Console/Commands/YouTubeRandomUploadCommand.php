<?php

namespace App\Console\Commands;

use App\Models\Track;
use App\Models\YouTubeAccount;
use App\Services\SimpleYouTubeUploader;
use App\Services\YouTubeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class YouTubeRandomUploadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:upload-random 
                            {--account= : Optional YouTube account ID to use for the upload}
                            {--privacy=public : Privacy status (public, unlisted, private)}
                            {--short : Upload as a YouTube Short}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finds a random track and uploads it to YouTube';

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
        $this->info('Starting random YouTube upload process...');

        // Check if a specific account should be used
        $accountId = $this->option('account');
        if ($accountId) {
            $account = YouTubeAccount::find($accountId);
            if (!$account) {
                $this->error("YouTube account with ID {$accountId} not found.");
                $this->info('Run "php artisan youtube:accounts" to see available accounts.');
                return 1;
            }

            $this->info("Using YouTube account: {$account->getDisplayName()} (ID: {$account->id})");
            $success = $this->youtubeService->setAccount($account);
            
            if (!$success) {
                $this->error("Failed to set account as active. The account may have expired credentials.");
                return 1;
            }
        } else {
            $activeAccount = YouTubeAccount::getActive();
            if ($activeAccount) {
                $this->info("Using active YouTube account: {$activeAccount->getDisplayName()} (ID: {$activeAccount->id})");
            } else {
                $this->info("No specific account specified. Using default YouTube credentials.");
            }
        }

        // Use SimpleYouTubeUploader for direct and simple upload
        $uploader = app(SimpleYouTubeUploader::class);
        
        // Check if YouTube service is authenticated
        if (!$uploader->isAuthenticated()) {
            $this->error('YouTube service is not authenticated. Please set up authentication first.');
            return 1;
        }

        // Get a random track that is completed, has mp4 file, and hasn't been uploaded to YouTube yet
        $track = Track::where('status', 'completed')
            ->whereNotNull('mp4_path')
            ->whereNull('youtube_video_id')
            ->inRandomOrder()
            ->first();

        if (!$track) {
            $this->info('No eligible tracks found for YouTube upload. All tracks may already be uploaded or not processed yet.');
            return 0;
        }

        $this->info("Selected track: {$track->title} (ID: {$track->id})");

        // Get privacy and short options
        $privacy = $this->option('privacy');
        $isShort = $this->option('short');
        
        // Create description in the same format as the web interface
        $description = "Track: {$track->title}\nGenres: {$track->genres_string}";
        
        $this->info("Uploading as " . ($isShort ? "YouTube Short" : "regular YouTube video") . " with {$privacy} privacy...");

        // Attempt to upload to YouTube
        try {
            // Directly upload the track using SimpleYouTubeUploader using the same format as web interface
            $videoId = $uploader->uploadTrack(
                $track,
                null, // Use default title from database
                $description, // Use same description format as web interface
                $privacy,
                true, // Add to playlists
                $isShort
            );
            
            $this->info("Success! Track '{$track->title}' was uploaded to YouTube with video ID: {$videoId}");
            $this->info("YouTube URL: https://www.youtube.com/watch?v={$videoId}");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error during upload: {$e->getMessage()}");
            Log::error("YouTubeRandomUploadCommand error: {$e->getMessage()}", [
                'track_id' => $track->id,
                'exception' => $e,
            ]);
            return 1;
        }
    }
} 