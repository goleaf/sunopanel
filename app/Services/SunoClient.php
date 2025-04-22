<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SunoClient
{
    /**
     * The Suno AI cookie for authentication
     * 
     * @var string
     */
    protected string $cookie;
    
    /**
     * The base URL for Suno API
     * 
     * @var string
     */
    protected string $baseUrl = 'https://app.suno.ai';
    
    /**
     * Create a new Suno client instance
     * 
     * @param string $cookie The authentication cookie
     * @return void
     */
    public function __construct(string $cookie)
    {
        $this->cookie = $cookie;
    }
    
    /**
     * Get tracks by style/genre from Suno
     * 
     * @param string $style The style/genre to search for
     * @return array The list of tracks
     * @throws Exception If the request fails
     */
    public function getTracksByStyle(string $style): array
    {
        try {
            // Extract the genre from URL if a full URL is provided
            if (str_contains($style, '/')) {
                $parts = explode('/', $style);
                $style = end($parts);
            }
            
            // Decode URL-encoded style
            $style = urldecode($style);
            
            // Make request to Suno API
            $response = Http::withHeaders([
                'Cookie' => $this->cookie,
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/api/v1/library", [
                'limit' => 100,
                'search_term' => $style,
                'include_trashed' => false,
            ]);
            
            if (!$response->successful()) {
                throw new Exception("Failed to get tracks from Suno API. Status: {$response->status()}");
            }
            
            $data = $response->json();
            
            // Format the tracks data
            $tracks = [];
            foreach ($data['clips'] ?? [] as $clip) {
                $tracks[] = [
                    'id' => $clip['id'] ?? null,
                    'title' => $clip['title'] ?? 'Untitled',
                    'audio_url' => $clip['audio_url'] ?? null,
                    'image_url' => $clip['image_url'] ?? null,
                    'image_large_url' => $clip['image_large_url'] ?? null,
                    'video_url' => $clip['video_url'] ?? null,
                    'tags' => $clip['metadata']['tags'] ?? null,
                    'prompt' => $clip['metadata']['prompt'] ?? null,
                    'duration' => $clip['metadata']['duration'] ?? null,
                    'created_at' => $clip['created_at'] ?? null,
                    'model_name' => $clip['model_name'] ?? null,
                    'play_count' => $clip['play_count'] ?? 0,
                    'upvote_count' => $clip['upvote_count'] ?? 0,
                    'is_public' => $clip['is_public'] ?? false,
                ];
            }
            
            return $tracks;
        } catch (Exception $e) {
            Log::error("Suno API error: {$e->getMessage()}");
            throw new Exception("Failed to fetch tracks from Suno: {$e->getMessage()}");
        }
    }
} 