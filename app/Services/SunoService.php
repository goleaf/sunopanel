<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class SunoService
{
    /**
     * Base URL for Suno API
     */
    protected string $baseUrl = 'https://suno.com';

    /**
     * Fetch tracks by music style from Suno.com
     * 
     * Note: Due to Cloudflare protection on Suno's website, this method currently returns mock data.
     * A production version would require a more sophisticated approach to bypass the protection.
     *
     * @param string $style Music style to fetch tracks for (e.g. "dark trap metalcore")
     * @return array Array of track data
     * @throws RequestException If the request to Suno fails
     */
    public function getTracksByStyle(string $style): array
    {
        try {
            // Check if we should use mock data
            // In a production environment, you'd implement proper Cloudflare bypass or use official API
            if (true) {
                return $this->getMockDataForStyle($style);
            }
            
            // The code below is kept for reference but won't be executed
            // Normalize the style by encoding it for URL
            $encodedStyle = urlencode($style);
            
            // Make request to Suno's style page (directly to the style page, not the API)
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
                'Cache-Control' => 'max-age=0',
            ])->get("{$this->baseUrl}/style/{$encodedStyle}");
            
            // Check if request was successful
            if ($response->failed()) {
                Log::error('Suno web request failed', [
                    'style' => $style,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500), // Log only part of the body to avoid huge logs
                ]);
                
                $response->throw();
            }
            
            // The response is HTML, we need to extract the data
            $html = $response->body();
            
            // Extract the JSON data from the __NEXT_DATA__ script
            $matches = [];
            if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
                $data = json_decode($matches[1], true);
                
                // Extract the track data from the JSON
                // The structure might be different, adjust as needed
                if (isset($data['props']['pageProps']['songs'])) {
                    return $data['props']['pageProps']['songs'];
                } elseif (isset($data['props']['pageProps']['tracks'])) {
                    return $data['props']['pageProps']['tracks'];
                } elseif (isset($data['props']['pageProps']['initialData']['songs'])) {
                    return $data['props']['pageProps']['initialData']['songs'];
                }
            }
            
            // If we couldn't extract the data or no tracks were found, return an empty array
            Log::warning('Could not extract track data from Suno HTML response', [
                'style' => $style,
            ]);
            
            return [];
        } catch (\Exception $e) {
            Log::error('Error fetching tracks from Suno', [
                'style' => $style,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Get mock data for a given style
     * 
     * @param string $style
     * @return array
     */
    private function getMockDataForStyle(string $style): array
    {
        // Convert style to lowercase for easier comparison
        $lowerStyle = strtolower($style);
        
        // If looking for "city pop" style
        if (strpos($lowerStyle, 'city pop') !== false) {
            return [
                [
                    'id' => 'citypop001',
                    'title' => 'Fleeting Love',
                    'artist' => 'Suno AI',
                    'style' => 'city pop',
                    'duration' => '4:10',
                    'url' => 'https://cdn1.suno.ai/69c0d3c4-a06f-471e-a396-4cb09c9ec2b6.mp3',
                    'cover' => 'https://cdn2.suno.ai/image_a07fbe33-8ee5-4f91-bb8d-180cbb49e5fe.jpeg',
                    'created_at' => '2023-07-18',
                ],
                [
                    'id' => 'citypop002',
                    'title' => 'Palakpakan',
                    'artist' => 'Suno AI',
                    'style' => 'city pop',
                    'duration' => '3:58',
                    'url' => 'https://cdn1.suno.ai/9a00dc20-9640-4150-9804-d8a179ce860c.mp3',
                    'cover' => 'https://cdn2.suno.ai/image_9a00dc20-9640-4150-9804-d8a179ce860c.jpeg',
                    'created_at' => '2023-08-05',
                ],
                [
                    'id' => 'citypop003',
                    'title' => 'ジャカジャカ',
                    'artist' => 'Suno AI',
                    'style' => 'city pop',
                    'duration' => '4:22',
                    'url' => 'https://cdn1.suno.ai/837cd038-c104-405b-b1d5-bafa924a277f.mp3',
                    'cover' => 'https://cdn2.suno.ai/image_837cd038-c104-405b-b1d5-bafa924a277f.jpeg',
                    'created_at' => '2023-09-30',
                ],
            ];
        }
        
        // If looking for "pop" style (but not city pop)
        if (strpos($lowerStyle, 'pop') !== false && strpos($lowerStyle, 'city pop') === false) {
            return [
                [
                    'id' => 'pop001',
                    'title' => 'Summer Breeze',
                    'artist' => 'Suno AI',
                    'style' => 'pop',
                    'duration' => '3:45',
                    'url' => 'https://cdn1.suno.ai/79ef4474-cea8-433a-9bdc-5fe3482fd410.mp3',
                    'cover' => 'https://cdn2.suno.ai/79ef4474-cea8-433a-9bdc-5fe3482fd410_e90ebc18.jpeg',
                    'created_at' => '2023-09-15',
                ],
                [
                    'id' => 'pop002',
                    'title' => 'City Lights',
                    'artist' => 'Suno AI',
                    'style' => 'pop',
                    'duration' => '3:20',
                    'url' => 'https://cdn1.suno.ai/e0ec684c-5a04-42f2-a656-6ee8fa05c880.mp3',
                    'cover' => 'https://cdn2.suno.ai/e0ec684c-5a04-42f2-a656-6ee8fa05c880_ffc2643f.jpeg',
                    'created_at' => '2023-08-22',
                ],
                [
                    'id' => 'pop003',
                    'title' => 'Radio Waves',
                    'artist' => 'Suno AI',
                    'style' => 'pop',
                    'duration' => '3:35',
                    'url' => 'https://cdn1.suno.ai/8d0149d6-236d-4d2b-9d2e-0923a4311895.mp3',
                    'cover' => 'https://cdn2.suno.ai/8d0149d6-236d-4d2b-9d2e-0923a4311895_cbda2f8e.jpeg',
                    'created_at' => '2023-10-05',
                ],
                [
                    'id' => 'pop004',
                    'title' => 'Electric Kinetic',
                    'artist' => 'Suno AI',
                    'style' => 'pop',
                    'duration' => '3:15',
                    'url' => 'https://cdn1.suno.ai/a5fb3403-74fd-4819-9417-409af40e603c.mp3',
                    'cover' => 'https://cdn2.suno.ai/a5fb3403-74fd-4819-9417-409af40e603c_2c38cfb6.jpeg',
                    'created_at' => '2023-11-12',
                ],
            ];
        }
        
        // Default to some random tracks for any other style
        return [
            [
                'id' => 'generic001',
                'title' => 'Music Track 1',
                'artist' => 'Suno AI',
                'style' => $style,
                'duration' => '3:30',
                'url' => 'https://example.com/track1.mp3',
                'cover' => 'https://example.com/track1.jpg',
                'created_at' => '2023-05-10',
            ],
            [
                'id' => 'generic002',
                'title' => 'Music Track 2',
                'artist' => 'Suno AI',
                'style' => $style,
                'duration' => '4:15',
                'url' => 'https://example.com/track2.mp3',
                'cover' => 'https://example.com/track2.jpg',
                'created_at' => '2023-06-15',
            ],
        ];
    }
} 