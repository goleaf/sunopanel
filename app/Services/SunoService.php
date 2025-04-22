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
            
            // Make request to Suno's style page
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/api/style/{$encodedStyle}");
            
            // Check if request was successful
            if ($response->failed()) {
                Log::error('Suno API request failed', [
                    'style' => $style,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                
                $response->throw();
            }
            
            // Parse the response JSON
            $tracks = $response->json();
            
            // If no tracks were returned, return an empty array
            if (!$tracks) {
                return [];
            }
            
            return $tracks;
        } catch (\Exception $e) {
            Log::error('Error fetching tracks from Suno', [
                'style' => $style,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
} 