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
     * @param string $style Music style to fetch tracks for (e.g. "dark trap metalcore")
     * @return array Array of track data
     * @throws RequestException If the request to Suno fails
     */
    public function getTracksByStyle(string $style): array
    {
        try {
            // Normalize the style by encoding it for URL
            $encodedStyle = urlencode($style);
            
            // Make request to Suno's style page (directly to the style page, not the API)
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
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
} 