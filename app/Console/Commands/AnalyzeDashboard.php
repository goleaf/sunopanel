<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AnalyzeDashboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analyze:dashboard {--api-key= : The API key for the Vision AI service}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze a dashboard screenshot using AI Vision API and generate design recommendations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiKey = $this->option('api-key') ?? config('services.vision_ai.api_key');
        
        if (!$apiKey) {
            $this->error('API key is required. Please provide it with --api-key option or set it in your .env file.');
            return 1;
        }
        
        $screenshotPath = storage_path('app/ai-analysis/dashboard-analysis.png');
        
        if (!file_exists($screenshotPath)) {
            $this->error('Dashboard screenshot not found. Run dashboard:analyze-design first.');
            return 1;
        }
        
        $this->info('Analyzing dashboard screenshot...');
        
        try {
            // Read the image file and encode it as base64
            $imageBase64 = base64_encode(file_get_contents($screenshotPath));
            
            // Send the image to the AI Vision API
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4-vision-preview',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a UI/UX expert. Analyze this dashboard screenshot and provide specific, actionable design improvements focused on: 1) Layout and spacing, 2) Color scheme and contrast, 3) Typography, 4) Responsive design considerations, 5) Accessibility improvements, and 6) Overall user experience. Format your response in Markdown.'
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Analyze this dashboard screenshot and provide detailed UI/UX improvement recommendations:'
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:image/png;base64,{$imageBase64}",
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 4000
            ]);
            
            // Check if the request was successful
            if ($response->successful()) {
                $analysisResult = $response->json()['choices'][0]['message']['content'];
                
                // Save the analysis results
                Storage::put('ai-analysis/design-recommendations.md', $analysisResult);
                
                $this->info('Analysis completed successfully!');
                $this->info('Results saved to: storage/app/ai-analysis/design-recommendations.md');
                
                // Show a preview of the recommendations
                $this->line('');
                $this->line('Preview of recommendations:');
                $this->line('-------------------------');
                $this->line(substr($analysisResult, 0, 500) . '...');
                $this->line('');
                
                return 0;
            } else {
                $this->error('API request failed: ' . $response->body());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Error analyzing screenshot: ' . $e->getMessage());
            return 1;
        }
    }
} 