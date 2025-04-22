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
    protected $description = 'Test the Suno API search functionality';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing Suno API search...');

        $response = Http::withHeaders([
            'accept' => '*/*',
            'accept-language' => 'en-US,en;q=0.9,de;q=0.8,ru;q=0.7',
            'affiliate-id' => 'undefined',
            'authorization' => 'Bearer eyJhbGciOiJSUzI1NiIsImNhdCI6ImNsX0I3ZDRQRDExMUFBQSIsImtpZCI6Imluc18yT1o2eU1EZzhscWRKRWloMXJvemY4T3ptZG4iLCJ0eXAiOiJKV1QifQ.eyJhdWQiOiJzdW5vLWFwaSIsImF6cCI6Imh0dHBzOi8vc3Vuby5jb20iLCJleHAiOjE3NDUzMjgyNTEsImZ2YSI6Wzg1ODAsLTFdLCJodHRwczovL3N1bm8uYWkvY2xhaW1zL2NsZXJrX2lkIjoidXNlcl8yalRZbUxScVYzSDA3VDFCeDdFb3IxcWpNV20iLCJodHRwczovL3N1bm8uYWkvY2xhaW1zL2VtYWlsIjoiZ29sZWFmQGdtYWlsLmNvbSIsImh0dHBzOi8vc3Vuby5haS9jbGFpbXMvcGhvbmUiOm51bGwsImlhdCI6MTc0NTMyODE5MSwiaXNzIjoiaHR0cHM6Ly9jbGVyay5zdW5vLmNvbSIsImp0aSI6IjhkOWFkY2FlZWU5MjBiMmNlNTAwIiwibmJmIjoxNzQ1MzI4MTgxLCJzaWQiOiJzZXNzXzJ2b1pNTmZDYmFvaEpJYk1EOE9WaG5uOUt2QyIsInN1YiI6InVzZXJfMmpUWW1MUnFWM0gwN1QxQng3RW9yMXFqTVdtIn0.pLKPAsc2WjHfcKDpPV-hJ1y5VYFmxQw6CqIvyLs-tCZtunpHMrmfTG5Mcw17FFj_0kiP9lrvImbh5YtrDBO2SqC0Q2-xXy9hsLtbNfAKLyRUSBzJQXgSfhYu70fX0pM0p2mJiwXFPQtvVGLB5F3DzZFMlnPKl_7-t6KX8fzwbG4n8MPEl8Ealg7j8ang-9Pt2J_1Y2HUTRWoMoRWzkNu3eYiRTIKlLT-bhqPHk9u77QPG4NlVzG0SEsLaCvmUFU0lYqVbfRWAkQ51z5LlcNZ3Ts6KIrZd0wW9CsS9mTTy3uOxbDo0P_HBceI4deL3zzcgqe36zvXnDs_3-moL3CDsg',
            'browser-token' => '{"token":"eyJ0aW1lc3RhbXAiOjE3NDUzMjgxOTk5ODd9"}',
            'content-type' => 'text/plain;charset=UTF-8',
            'device-id' => '42c6837d-7c0f-4093-b9bc-10d1671749aa',
            'dnt' => '1',
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

        if ($response->successful()) {
            $this->info('Request successful!');
            $this->line('Response:');
            $this->line(json_encode($response->json(), JSON_PRETTY_PRINT));
            
            return self::SUCCESS;
        }

        $this->error('Request failed with status code: ' . $response->status());
        $this->line('Response body:');
        $this->line($response->body());
        
        return self::FAILURE;
    }
}
