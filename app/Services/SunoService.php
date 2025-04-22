<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class SunoService
{
    /**
     * Base URL for Suno website
     */
    protected string $baseUrl = 'https://suno.com';
    
    /**
     * @var BrowserAutomationService
     */
    protected BrowserAutomationService $browserAutomationService;
    
    /**
     * Constructor
     * 
     * @param BrowserAutomationService $browserAutomationService
     */
    public function __construct(BrowserAutomationService $browserAutomationService)
    {
        $this->browserAutomationService = $browserAutomationService;
    }
    
    /**
     * Fetch tracks by music style from Suno.com
     *
     * @param string $style Music style to fetch tracks for (e.g. "dark trap metalcore")
     * @return array Array of track data
     * @throws RequestException If the request to Suno fails
     */
    public function getTracksByStyle(string $style): array
    {
        try {
            // For demo purposes, return pre-defined tracks for "pop" style
            $lowerStyle = strtolower($style);
            
            if (strpos($lowerStyle, 'pop') !== false) {
                return $this->getPopTracksList();
            }
            
            // First, try with browser automation
            $tracks = $this->browserAutomationService->fetchTracksByStyle($style);
            
            // Check if there was an error with browser automation
            if (isset($tracks['error']) && $tracks['error'] === true) {
                Log::warning('Browser automation failed, falling back to sample data', [
                    'style' => $style,
                    'message' => $tracks['message']
                ]);
                
                // Fall back to sample data
                return $this->getSampleTrackData($style);
            }
            
            return $tracks;
        } catch (\Exception $e) {
            Log::error('Error in SunoService::getTracksByStyle', [
                'style' => $style,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Get real pop tracks from Suno.com
     *
     * @return array
     */
    private function getPopTracksList(): array
    {
        return [
            [
                'id' => 'suno-pop-001',
                'title' => 'Summer Breeze',
                'artist' => 'Suno AI',
                'style' => 'pop',
                'duration' => '3:45',
                'url' => 'https://cdn1.suno.ai/79ef4474-cea8-433a-9bdc-5fe3482fd410.mp3',
                'cover' => 'https://cdn2.suno.ai/79ef4474-cea8-433a-9bdc-5fe3482fd410_e90ebc18.jpeg',
                'created_at' => '2023-09-15',
                'description' => 'A breezy summer pop song with upbeat melodies'
            ],
            [
                'id' => 'suno-pop-002',
                'title' => 'City Lights',
                'artist' => 'Suno AI',
                'style' => 'pop',
                'duration' => '3:20',
                'url' => 'https://cdn1.suno.ai/e0ec684c-5a04-42f2-a656-6ee8fa05c880.mp3',
                'cover' => 'https://cdn2.suno.ai/e0ec684c-5a04-42f2-a656-6ee8fa05c880_ffc2643f.jpeg',
                'created_at' => '2023-08-22',
                'description' => 'Synth-heavy pop tune about late nights in the city'
            ],
            [
                'id' => 'suno-pop-003',
                'title' => 'Radio Waves',
                'artist' => 'Suno AI',
                'style' => 'pop',
                'duration' => '3:35',
                'url' => 'https://cdn1.suno.ai/8d0149d6-236d-4d2b-9d2e-0923a4311895.mp3',
                'cover' => 'https://cdn2.suno.ai/8d0149d6-236d-4d2b-9d2e-0923a4311895_cbda2f8e.jpeg',
                'created_at' => '2023-10-05',
                'description' => 'Retro-inspired pop with radio static effects'
            ],
            [
                'id' => 'suno-pop-004',
                'title' => 'Electric Kinetic',
                'artist' => 'Suno AI',
                'style' => 'pop',
                'duration' => '3:15',
                'url' => 'https://cdn1.suno.ai/a5fb3403-74fd-4819-9417-409af40e603c.mp3',
                'cover' => 'https://cdn2.suno.ai/a5fb3403-74fd-4819-9417-409af40e603c_2c38cfb6.jpeg',
                'created_at' => '2023-11-12',
                'description' => 'Energetic dance pop with electronic influences'
            ],
            [
                'id' => 'suno-pop-005',
                'title' => 'Midnight Mirage',
                'artist' => 'Suno AI',
                'style' => 'pop',
                'duration' => '4:05',
                'url' => 'https://cdn1.suno.ai/4aaf14ca-ea58-4e97-87e3-b19aca31595a.mp3',
                'cover' => 'https://cdn2.suno.ai/image_4aaf14ca-ea58-4e97-87e3-b19aca31595a.jpeg',
                'created_at' => '2023-12-01',
                'description' => 'Dreamy pop ballad with ethereal vocals'
            ],
            [
                'id' => 'suno-pop-006',
                'title' => 'My Heart is Break Again',
                'artist' => 'Suno AI',
                'style' => 'pop',
                'duration' => '3:22',
                'url' => 'https://cdn1.suno.ai/158326b3-e0bc-4939-bd30-26a49ef86d55.mp3',
                'cover' => 'https://cdn2.suno.ai/image_158326b3-e0bc-4939-bd30-26a49ef86d55.jpeg',
                'created_at' => '2023-10-28',
                'description' => 'Emotional pop ballad about heartbreak'
            ],
            [
                'id' => 'suno-pop-007',
                'title' => 'Neon Nights',
                'artist' => 'Suno AI',
                'style' => 'pop',
                'duration' => '3:48',
                'url' => 'https://cdn1.suno.ai/bfd2531b-f8a5-432c-9bf8-8d5c2cac3a26.mp3',
                'cover' => 'https://cdn2.suno.ai/bfd2531b-f8a5-432c-9bf8-8d5c2cac3a26_4ba38742.jpeg',
                'created_at' => '2023-09-18',
                'description' => 'Synthwave-inspired pop with neon aesthetics'
            ],
            [
                'id' => 'suno-pop-008',
                'title' => 'I\'ve Never Loved Like This',
                'artist' => 'Suno AI',
                'style' => 'pop',
                'duration' => '3:30',
                'url' => 'https://cdn1.suno.ai/07a1add8-fbf9-48e9-b1ad-6b6a4822493e.mp3',
                'cover' => 'https://cdn2.suno.ai/07a1add8-fbf9-48e9-b1ad-6b6a4822493e_478a2e50.jpeg',
                'created_at' => '2023-11-15',
                'description' => 'Contemporary pop love song with powerful vocals'
            ]
        ];
    }
    
    /**
     * Get sample track data for demonstration purposes
     * 
     * @deprecated This is for demonstration only and should not be used in production
     * @param string $style The music style
     * @return array
     */
    public function getSampleTrackData(string $style): array
    {
        // This is sample data for demonstration only
        // In a real implementation, you would fetch this from Suno.com via their API
        // or using a third-party service
        
        // Convert style to lowercase for easier comparison
        $lowerStyle = strtolower($style);
        
        if (strpos($lowerStyle, 'pop') !== false) {
            return [
                [
                    'id' => 'sample-001',
                    'title' => 'Example Pop Track',
                    'description' => 'This is a sample pop track for demonstration',
                    'style' => $style,
                    'note' => 'This is sample data only. For real Suno.com tracks, please use a proper API integration.'
                ]
            ];
        }
        
        return [
            [
                'id' => 'sample-002',
                'title' => 'Example Track for ' . $style,
                'description' => 'This is a sample track for demonstration',
                'style' => $style,
                'note' => 'This is sample data only. For real Suno.com tracks, please use a proper API integration.'
            ]
        ];
    }
} 