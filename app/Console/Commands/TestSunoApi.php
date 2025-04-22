<?php

namespace App\Console\Commands;

use App\Services\CaptchaSolverService;
use App\Services\SunoService;
use Illuminate\Console\Command;

class TestSunoApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suno:test {style} {--api-key=} {--url=} {--browser=chrome}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Suno API by fetching tracks for a specific style';

    /**
     * Execute the console command.
     */
    public function handle(SunoService $sunoService, CaptchaSolverService $captchaSolverService)
    {
        $style = $this->argument('style');
        $apiKey = $this->option('api-key');
        
        if ($apiKey) {
            // Set the API key in the environment
            putenv("TWOCAPTCHA_API_KEY={$apiKey}");
        }
        
        $this->info("Testing Suno API for style: {$style}");
        
        // Check 2Captcha balance
        $balance = $captchaSolverService->getBalance();
        if ($balance !== null) {
            $this->info("2Captcha balance: \${$balance}");
        } else {
            $this->error("Failed to get 2Captcha balance. Please check your API key.");
            return 1;
        }
        
        // Fetch tracks
        $this->info("Fetching tracks for style: {$style}");
        $tracks = $sunoService->getTracksByStyle($style);
        
        if (isset($tracks['error']) && $tracks['error'] === true) {
            $this->error("Failed to fetch tracks: {$tracks['message']}");
            return 1;
        }
        
        $this->info("Found " . count($tracks) . " tracks for style: {$style}");
        
        // Display track information
        foreach ($tracks as $index => $track) {
            $this->line("\nTrack " . ($index + 1) . ":");
            
            if (isset($track['id'])) {
                $this->line("  ID: {$track['id']}");
            }
            
            if (isset($track['title'])) {
                $this->line("  Title: {$track['title']}");
            }
            
            if (isset($track['artist'])) {
                $this->line("  Artist: {$track['artist']}");
            }
            
            if (isset($track['duration'])) {
                $this->line("  Duration: {$track['duration']}");
            }
            
            if (isset($track['url'])) {
                $this->line("  URL: {$track['url']}");
            }
            
            if (isset($track['cover'])) {
                $this->line("  Cover: {$track['cover']}");
            }
        }
        
        return 0;
    }
} 