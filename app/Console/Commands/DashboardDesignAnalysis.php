<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class DashboardDesignAnalysis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:analyze-design {--api-key= : The API key for the Vision AI service}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Dusk tests to capture a dashboard screenshot and analyze it for design improvements';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting dashboard design analysis...');
        
        // Step 1: Create the required directories
        Storage::makeDirectory('ai-analysis');
        
        // Step 2: Run the Dusk test to capture screenshot
        $this->info('Running Dusk test to capture dashboard screenshot...');
        
        $duskResult = Artisan::call('dusk', [
            '--filter' => 'DesignAnalysisTest'
        ]);
        
        if ($duskResult !== 0) {
            $this->error('Dusk test failed. Unable to capture dashboard screenshot.');
            return 1;
        }
        
        $screenshotPath = storage_path('app/ai-analysis/dashboard-analysis.png');
        
        // Step 3: Check if screenshot was captured
        if (!file_exists($screenshotPath)) {
            $this->error('Screenshot was not found. Check the Dusk test output for errors.');
            return 1;
        }
        
        $this->info('Screenshot captured successfully!');
        
        // Step 4: Run the analysis command
        $this->info('Analyzing dashboard design...');
        
        $apiKey = $this->option('api-key');
        $analyzeCommand = 'analyze:dashboard';
        
        if ($apiKey) {
            $analyzeCommand .= ' --api-key=' . $apiKey;
        }
        
        $analysisResult = Artisan::call($analyzeCommand);
        
        if ($analysisResult !== 0) {
            $this->error('Design analysis failed. Check the logs for more information.');
            return 1;
        }
        
        $this->info('Dashboard design analysis completed successfully!');
        $this->info('Check the analysis results at: storage/app/ai-analysis/design-recommendations.md');
        
        return 0;
    }
} 