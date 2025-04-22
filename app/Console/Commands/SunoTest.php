<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SunoTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suno:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Suno style page functionality';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing Suno style page for "dark trap metalcore"...');
        
        // First request - Visit the style page
        $response = Http::withHeaders([
            'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'accept-language' => 'en-US,en;q=0.9,de;q=0.8,ru;q=0.7',
            'cache-control' => 'max-age=0',
            'priority' => 'u=0, i',
            'sec-ch-ua' => '"Google Chrome";v="135", "Not-A.Brand";v="8", "Chromium";v="135"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'document',
            'sec-fetch-mode' => 'navigate',
            'sec-fetch-site' => 'same-origin',
            'sec-fetch-user' => '?1',
            'upgrade-insecure-requests' => '1',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
        ])->get('https://suno.com/style/dark%20trap%20metalcore');

        if ($response->failed()) {
            $this->error('Failed to access the style page: ' . $response->status());
            $this->line('Response body:');
            $this->line($response->body());
            return self::FAILURE;
        }

        $this->info('Successfully accessed the style page.');
        
        // Make API search request to get songs data
        $this->info('Now fetching search results for "dark trap metalcore"...');
        
        $searchResponse = Http::withHeaders([
            'accept' => '*/*',
            'accept-language' => 'en-US,en;q=0.9,de;q=0.8,ru;q=0.7',
            'affiliate-id' => 'undefined',
            'authorization' => 'Bearer eyJhbGciOiJSUzI1NiIsImNhdCI6ImNsX0I3ZDRQRDExMUFBQSIsImtpZCI6Imluc18yT1o2eU1EZzhscWRKRWloMXJvemY4T3ptZG4iLCJ0eXAiOiJKV1QifQ.eyJhdWQiOiJzdW5vLWFwaSIsImF6cCI6Imh0dHBzOi8vc3Vuby5jb20iLCJleHAiOjE3NDUzMjgyNTEsImZ2YSI6Wzg1ODAsLTFdLCJodHRwczovL3N1bm8uYWkvY2xhaW1zL2NsZXJrX2lkIjoidXNlcl8yalRZbUxScVYzSDA3VDFCeDdFb3IxcWpNV20iLCJodHRwczovL3N1bm8uYWkvY2xhaW1zL2VtYWlsIjoiZ29sZWFmQGdtYWlsLmNvbSIsImh0dHBzOi8vc3Vuby5haS9jbGFpbXMvcGhvbmUiOm51bGwsImlhdCI6MTc0NTMyODE5MSwiaXNzIjoiaHR0cHM6Ly9jbGVyay5zdW5vLmNvbSIsImp0aSI6IjhkOWFkY2FlZWU5MjBiMmNlNTAwIiwibmJmIjoxNzQ1MzI4MTgxLCJzaWQiOiJzZXNzXzJ2b1pNTmZDYmFvaEpJYk1EOE9WaG5uOUt2QyIsInN1YiI6InVzZXJfMmpUWW1MUnFWM0gwN1QxQng3RW9yMXFqTVdtIn0.pLKPAsc2WjHfcKDpPV-hJ1y5VYFmxQw6CqIvyLs-tCZtunpHMrmfTG5Mcw17FFj_0kiP9lrvImbh5YtrDBO2SqC0Q2-xXy9hsLtbNfAKLyRUSBzJQXgSfhYu70fX0pM0p2mJiwXFPQtvVGLB5F3DzZFMlnPKl_7-t6KX8fzwbG4n8MPEl8Ealg7j8ang-9Pt2J_1Y2HUTRWoMoRWzkNu3eYiRTIKlLT-bhqPHk9u77QPG4NlVzG0SEsLaCvmUFU0lYqVbfRWAkQ51z5LlcNZ3Ts6KIrZd0wW9CsS9mTTy3uOxbDo0P_HBceI4deL3zzcgqe36zvXnDs_3-moL3CDsg',
            'browser-token' => '{"token":"eyJ0aW1lc3RhbXAiOjE3NDUzMjgxOTk5ODd9"}',
            'content-type' => 'text/plain;charset=UTF-8',
            'device-id' => '42c6837d-7c0f-4093-b9bc-10d1671749aa',
            'origin' => 'https://suno.com',
            'priority' => 'u=1, i',
            'referer' => 'https://suno.com/',
            'sec-ch-ua' => '"Google Chrome";v="135", "Not-A.Brand";v="8", "Chromium";v="135"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-site',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
        ])->post('https://studio-api.prod.suno.com/api/search/', [
            'search_queries' => [
                [
                    'name' => 'tag_song',
                    'search_type' => 'tag_song',
                    'term' => 'dark trap metalcore',
                    'from_index' => 0,
                    'rank_by' => 'most_relevant'
                ]
            ]
        ]);

        if ($searchResponse->failed()) {
            $this->error('Failed to fetch search results: ' . $searchResponse->status());
            return self::FAILURE;
        }

        $this->info('Search results fetched successfully.');
        
        // Get song details for the results
        $songs = $searchResponse->json('results.0.songs') ?? [];
        
        if (empty($songs)) {
            $this->warn('No songs found for this style.');
            return self::SUCCESS;
        }
        
        // Get song IDs
        $songIds = array_column($songs, 'id');
        
        $this->info('Found ' . count($songIds) . ' songs. Fetching song details...');
        
        // Build query string for song IDs
        $songsQuery = '';
        foreach ($songIds as $id) {
            $songsQuery .= "ids={$id}&";
        }
        $songsQuery = rtrim($songsQuery, '&');
        
        // Get detailed song information
        $songsResponse = Http::withHeaders([
            'accept' => '*/*',
            'accept-language' => 'en-US,en;q=0.9,de;q=0.8,ru;q=0.7',
            'affiliate-id' => 'undefined',
            'authorization' => 'Bearer eyJhbGciOiJSUzI1NiIsImNhdCI6ImNsX0I3ZDRQRDExMUFBQSIsImtpZCI6Imluc18yT1o2eU1EZzhscWRKRWloMXJvemY4T3ptZG4iLCJ0eXAiOiJKV1QifQ.eyJhdWQiOiJzdW5vLWFwaSIsImF6cCI6Imh0dHBzOi8vc3Vuby5jb20iLCJleHAiOjE3NDUzMjgyNTEsImZ2YSI6Wzg1ODAsLTFdLCJodHRwczovL3N1bm8uYWkvY2xhaW1zL2NsZXJrX2lkIjoidXNlcl8yalRZbUxScVYzSDA3VDFCeDdFb3IxcWpNV20iLCJodHRwczovL3N1bm8uYWkvY2xhaW1zL2VtYWlsIjoiZ29sZWFmQGdtYWlsLmNvbSIsImh0dHBzOi8vc3Vuby5haS9jbGFpbXMvcGhvbmUiOm51bGwsImlhdCI6MTc0NTMyODE5MSwiaXNzIjoiaHR0cHM6Ly9jbGVyay5zdW5vLmNvbSIsImp0aSI6IjhkOWFkY2FlZWU5MjBiMmNlNTAwIiwibmJmIjoxNzQ1MzI4MTgxLCJzaWQiOiJzZXNzXzJ2b1pNTmZDYmFvaEpJYk1EOE9WaG5uOUt2QyIsInN1YiI6InVzZXJfMmpUWW1MUnFWM0gwN1QxQng3RW9yMXFqTVdtIn0.pLKPAsc2WjHfcKDpPV-hJ1y5VYFmxQw6CqIvyLs-tCZtunpHMrmfTG5Mcw17FFj_0kiP9lrvImbh5YtrDBO2SqC0Q2-xXy9hsLtbNfAKLyRUSBzJQXgSfhYu70fX0pM0p2mJiwXFPQtvVGLB5F3DzZFMlnPKl_7-t6KX8fzwbG4n8MPEl8Ealg7j8ang-9Pt2J_1Y2HUTRWoMoRWzkNu3eYiRTIKlLT-bhqPHk9u77QPG4NlVzG0SEsLaCvmUFU0lYqVbfRWAkQ51z5LlcNZ3Ts6KIrZd0wW9CsS9mTTy3uOxbDo0P_HBceI4deL3zzcgqe36zvXnDs_3-moL3CDsg',
            'browser-token' => '{"token":"eyJ0aW1lc3RhbXAiOjE3NDUzMjgyMDAzNDd9"}',
            'device-id' => '42c6837d-7c0f-4093-b9bc-10d1671749aa',
            'priority' => 'u=1, i',
            'referer' => 'https://suno.com/',
            'sec-ch-ua' => '"Google Chrome";v="135", "Not-A.Brand";v="8", "Chromium";v="135"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-site',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
        ])->get("https://studio-api.prod.suno.com/api/clips/get_songs_by_ids?{$songsQuery}");
        
        if ($songsResponse->failed()) {
            $this->error('Failed to fetch song details: ' . $songsResponse->status());
            return self::FAILURE;
        }
        
        // Display song information
        $this->info('Song details for "dark trap metalcore":');
        $this->newLine();
        
        $songDetails = $songsResponse->json('songs') ?? [];
        foreach ($songDetails as $song) {
            $this->line('--------------------------------------------------');
            $this->line('Title: ' . ($song['title'] ?? 'Unknown'));
            $this->line('Artist: ' . ($song['artist'] ?? 'Unknown'));
            $this->line('MP3 URL: ' . ($song['mp3_url'] ?? 'Unknown'));
            $this->line('Image URL: ' . ($song['image_url'] ?? 'Unknown'));
            $this->line('Tags: ' . implode(', ', $song['tags'] ?? []));
            $this->newLine();
        }
        
        $this->info('Simulation completed successfully!');
        return self::SUCCESS;
    }
}
