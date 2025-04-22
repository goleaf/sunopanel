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