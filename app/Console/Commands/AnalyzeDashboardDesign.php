<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalyzeDashboardDesign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:analyze
                          {--api-key= : OpenAI API key (defaults to OPENAI_API_KEY env variable)}
                          {--screenshot= : Path to screenshot file (defaults to storage/app/ai-analysis/dashboard-analysis.png)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze dashboard design using OpenAI Vision API and provide improvement recommendations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting dashboard design analysis...');

        // Get API key from option or env
        $apiKey = $this->option('api-key') ?: env('OPENAI_API_KEY');
        if (!$apiKey) {
            $this->error('OpenAI API key is required. Provide it with --api-key option or set OPENAI_API_KEY in your .env file.');
            return 1;
        }

        // Get screenshot path
        $screenshotPath = $this->option('screenshot') ?: storage_path('app/ai-analysis/dashboard-analysis.png');
        if (!File::exists($screenshotPath)) {
            $this->error("Screenshot not found at: $screenshotPath");
            $this->info("Run 'php artisan dusk tests/Browser/DesignAnalysisTest.php' to generate the screenshot.");
            return 1;
        }

        try {
            // Read and encode image
            $imageData = base64_encode(File::get($screenshotPath));
            
            $this->info('Sending screenshot to OpenAI for analysis...');
            
            // Make API request
            $response = Http::withHeaders([
                'Authorization' => "Bearer $apiKey",
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4-vision-preview',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a UI/UX expert who specializes in analyzing web dashboards and providing specific, actionable design improvements.'
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "Analyze this dashboard screenshot and provide specific design improvement recommendations in the following areas:\n\n1. Color scheme and visual hierarchy\n2. Layout and spacing\n3. Typography and readability\n4. Data visualization effectiveness\n5. Modern UI/UX best practices\n6. Accessibility considerations\n\nFor each recommendation, explain why it would improve the user experience."
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:image/png;base64,$imageData"
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 1500
            ]);

            // Check if request was successful
            if ($response->successful()) {
                $result = $response->json();
                $analysis = $result['choices'][0]['message']['content'] ?? 'No analysis returned.';
                
                // Save analysis to file
                $outputDir = storage_path('app/ai-analysis');
                if (!File::isDirectory($outputDir)) {
                    File::makeDirectory($outputDir, 0755, true);
                }
                
                $outputPath = "$outputDir/design-recommendations.md";
                File::put($outputPath, "# Dashboard Design Analysis\n\n" . $analysis);
                
                $this->info("Analysis complete! Recommendations saved to: $outputPath");
                $this->line("\nPreview of recommendations:");
                $this->line(substr($analysis, 0, 300) . '...');
                
                return 0;
            } else {
                $error = $response->json();
                $this->error("API Error: " . ($error['error']['message'] ?? 'Unknown error'));
                Log::error('OpenAI API error', $error);
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error('Dashboard analysis error', ['exception' => $e]);
            return 1;
        }
    }
} 