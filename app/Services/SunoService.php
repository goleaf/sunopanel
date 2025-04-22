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
     * Fetch tracks by music style from Suno.com
     *
     * @param string $style Music style to fetch tracks for (e.g. "dark trap metalcore")
     * @return array Array of track data
     * @throws RequestException If the request to Suno fails
     */
    public function getTracksByStyle(string $style): array
    {
        try {
            // Unfortunately, direct API access to Suno.com is challenging due to Cloudflare protection
            // For a production implementation, you would need to:
            // 1. Use a proper API like the ones offered by third-party services (e.g., PiAPI or others)
            // 2. Or implement a sophisticated browser automation with CAPTCHA solving
            
            // Since we can't directly access their data in a simple way without paying for services,
            // we'll return an error message with recommendations
            
            Log::info('Attempting to fetch tracks by style', [
                'style' => $style
            ]);
            
            return [
                'error' => true,
                'message' => 'Direct data fetching from Suno.com requires specialized authentication and CAPTCHA solving.',
                'recommendations' => [
                    'Use a third-party API service like PiAPI (https://piapi.ai/suno-api) or similar',
                    'Implement a browser automation solution with CAPTCHA solving (e.g., using the open-source suno-api project: https://github.com/gcui-art/suno-api)',
                    'Contact Suno directly for API access if you are a business partner'
                ],
                'style_requested' => $style
            ];
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