<?php

namespace App\Console\Commands;

use App\Models\Genre;
use App\Models\Track;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ImportSunoSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:suno-search 
                            {--term= : Search term (empty for all public songs)}
                            {--size=20 : Number of tracks per request}
                            {--pages=1 : Number of pages to fetch}
                            {--rank-by=most_relevant : Ranking method (upvote_count, play_count, dislike_count, trending, most_recent, most_relevant, by_hour, by_day, by_week, by_month, all_time, default)}
                            {--instrumental=false : Search for instrumental tracks only}
                            {--process : Automatically start processing imported tracks}
                            {--dry-run : Preview import without creating tracks}
                            {--session-id= : Session ID for progress tracking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import tracks from Suno search API (public songs, search by term, etc.)';

    /**
     * Suno API configuration
     */
    private const API_BASE_URL = 'https://studio-api.prod.suno.com/api/search/';
    private const BEARER_TOKEN = 'eyJhbGciOiJSUzI1NiIsImNhdCI6ImNsX0I3ZDRQRDExMUFBQSIsImtpZCI6Imluc18yT1o2eU1EZzhscWRKRWloMXJvemY4T3ptZG4iLCJ0eXAiOiJKV1QifQ.eyJhdWQiOiJzdW5vLWFwaSIsImF6cCI6Imh0dHBzOi8vc3Vuby5jb20iLCJleHAiOjE3NDg4NTkwOTUsImZ2YSI6WzEsLTFdLCJodHRwczovL3N1bm8uYWkvY2xhaW1zL2NsZXJrX2lkIjoidXNlcl8yalRZbUxScVYzSDA3VDFCeDdFb3IxcWpNV20iLCJodHRwczovL3N1bm8uYWkvY2xhaW1zL2VtYWlsIjoiZ29sZWFmQGdtYWlsLmNvbSIsImh0dHBzOi8vc3Vuby5haS9jbGFpbXMvcGhvbmUiOm51bGwsImlhdCI6MTc0ODg1OTAzNSwiaXNzIjoiaHR0cHM6Ly9jbGVyay5zdW5vLmNvbSIsImp0aSI6IjU5MWU4ZDcxZWJlNjE5MDcxMWIxIiwibmJmIjoxNzQ4ODU5MDI1LCJzaWQiOiJzZXNzXzJ4d3BQZnJkN1Y2ZlI1dlliQWZvbm02VTlxaiIsInN1YiI6InVzZXJfMmpUWW1MUnFWM0gwN1QxQng3RW9yMXFqTVdtIn0.Lgb8tIvPRksY6QIJcQpMg6VtN46Vzs0oI4w9zwxZ8UYFFMYgPL7PrtKX05nMpel1AFhNphzHI_oq4avMk0HB8b80ZKeUey0W_Xf3Au914UoXXMWX0twsCcIccQ4jz0h_XTjR-pVYiR7RhK2TKYuoL8vluKgBlm7H8A7baVTh9tEbrbDJXo7lAUrpbV2QQAnYUY_0XFr4mf-ayXEzf9MhV2GBM_If2drmyfVRQAxzdtcJC3VZTgp0uaDVH-qNFYzYx3a2y7fHQpxpmn2tPd26SDwxuSk9scromTw2gh1YG8P2Xq-o8_FL3x1OWUFPmAZt0hRPWpTForoI2Xd8DuovIg';
    private const BROWSER_TOKEN = '{"token":"eyJ0aW1lc3RhbXAiOjE3NDg4NTkwNDExOTB9"}';
    private const DEVICE_ID = '42c6837d-7c0f-4093-b9bc-10d1671749aa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $term = $this->option('term') ?? '';
        $size = (int) $this->option('size');
        $pages = (int) $this->option('pages');
        $rankBy = $this->option('rank-by');
        $instrumental = $this->option('instrumental') === 'true';
        $autoProcess = $this->option('process');
        $dryRun = $this->option('dry-run');
        $sessionId = $this->option('session-id');

        $this->info("Fetching tracks from Suno search API");
        $this->info("Search term: " . ($term ?: '(all public songs)'));
        $this->info("Size per page: {$size}");
        $this->info("Pages to fetch: {$pages}");
        $this->info("Rank by: {$rankBy}");
        $this->info("Instrumental only: " . ($instrumental ? 'yes' : 'no'));
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No tracks will be created');
        }

        if ($sessionId) {
            $this->updateProgress($sessionId, 10, 'Starting Suno Search import...');
        }

        $totalImported = 0;
        $totalFailed = 0;

        try {
            for ($page = 1; $page <= $pages; $page++) {
                $this->info("\nFetching page {$page}/{$pages}...");
                
                if ($sessionId) {
                    $pageProgress = 10 + (($page - 1) / $pages) * 80;
                    $this->updateProgress($sessionId, (int)$pageProgress, "Fetching page {$page}/{$pages}...", 'running', $totalImported, $totalFailed);
                }
                
                $fromIndex = ($page - 1) * $size;
                
                $tracks = $this->fetchTracksFromAPI($term, $fromIndex, $size, $rankBy, $instrumental);
                
                if (empty($tracks)) {
                    $this->warn("No tracks found on page {$page}");
                    continue;
                }

                $this->info("Found " . count($tracks) . " tracks on page {$page}");

                foreach ($tracks as $index => $trackData) {
                    try {
                        if ($dryRun) {
                            $this->displayTrackPreview($trackData, $totalImported + $index + 1);
                            $totalImported++;
                        } else {
                            $track = $this->processTrack($trackData);
                            
                            if ($track) {
                                $totalImported++;
                                
                                if ($autoProcess) {
                                    \App\Jobs\ProcessTrack::dispatch($track);
                                    $this->comment("Queued track for processing: {$track->title}");
                                }
                            }
                        }

                        // Update progress within page
                        if ($sessionId) {
                            $trackProgress = $pageProgress + (($index + 1) / count($tracks)) * (80 / $pages);
                            $this->updateProgress($sessionId, (int)$trackProgress, "Processing track " . ($totalImported) . "...", 'running', $totalImported, $totalFailed);
                        }
                    } catch (Exception $e) {
                        $totalFailed++;
                        $this->error("Failed to process track: " . $e->getMessage());
                        Log::error("Failed to process Suno search track", [
                            'track_data' => $trackData,
                            'error' => $e->getMessage(),
                        ]);

                        // Update progress with failure
                        if ($sessionId) {
                            $trackProgress = $pageProgress + (($index + 1) / count($tracks)) * (80 / $pages);
                            $this->updateProgress($sessionId, (int)$trackProgress, "Processing track " . ($totalImported + $totalFailed) . "...", 'running', $totalImported, $totalFailed);
                        }
                    }
                }
                
                // Add delay between pages to be respectful to the API
                if ($page < $pages) {
                    $this->comment("Waiting 2 seconds before next page...");
                    sleep(2);
                }
            }

            $message = $dryRun ? "DRY RUN: Would import {$totalImported} tracks" : "Import finished. Imported: {$totalImported}, Failed: {$totalFailed}";
            
            if ($sessionId) {
                $this->updateProgress($sessionId, 100, $message, 'completed', $totalImported, $totalFailed);
            }

            $this->newLine();
            $this->info($message);
            
            return $totalFailed > 0 ? 1 : 0;
        } catch (Exception $e) {
            if ($sessionId) {
                $this->updateProgress($sessionId, 100, 'Import failed: ' . $e->getMessage(), 'failed', $totalImported, $totalFailed);
            }
            
            $this->error("Failed to import from Suno search: " . $e->getMessage());
            Log::error("Failed to import from Suno search", [
                'term' => $term,
                'error' => $e->getMessage(),
            ]);
            return 1;
        }
    }

    /**
     * Fetch tracks from Suno search API.
     *
     * @param string $term
     * @param int $fromIndex
     * @param int $size
     * @param string $rankBy
     * @param bool $instrumental
     * @return array
     * @throws Exception
     */
    protected function fetchTracksFromAPI(string $term, int $fromIndex, int $size, string $rankBy, bool $instrumental): array
    {
        $payload = [
            'search_queries' => [
                [
                    'name' => 'public_song',
                    'search_type' => 'public_song',
                    'term' => $term,
                    'from_index' => $fromIndex,
                    'size' => $size,
                    'rank_by' => $rankBy,
                    'is_instrumental' => $instrumental,
                ]
            ]
        ];

        $response = Http::withHeaders([
            'accept' => '*/*',
            'accept-language' => 'en-US,en;q=0.9,de;q=0.8,ru;q=0.7',
            'affiliate-id' => 'undefined',
            'authorization' => 'Bearer ' . self::BEARER_TOKEN,
            'browser-token' => self::BROWSER_TOKEN,
            'content-type' => 'text/plain;charset=UTF-8',
            'device-id' => self::DEVICE_ID,
            'priority' => 'u=1, i',
            'sec-ch-ua' => '"Chromium";v="136", "Google Chrome";v="136", "Not.A/Brand";v="99"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-site',
        ])->withOptions([
            'referer' => 'https://suno.com/',
            'referrerPolicy' => 'strict-origin-when-cross-origin',
            'mode' => 'cors',
            'credentials' => 'include'
        ])->timeout(60)->post(self::API_BASE_URL, $payload);

        if (!$response->successful()) {
            $this->error("API Response Status: {$response->status()}");
            $this->error("API Response Body: " . $response->body());
            throw new Exception("Failed to fetch data from Suno search API. Status: {$response->status()}");
        }

        $data = $response->json();
        
        // Extract tracks from the search results structure
        if (!isset($data['result']) || !is_array($data['result'])) {
            throw new Exception("No result found in Suno search API response");
        }
        
        if (!isset($data['result']['public_song']) || !is_array($data['result']['public_song'])) {
            throw new Exception("No public_song data found in search results");
        }
        
        $publicSongData = $data['result']['public_song'];
        
        if (!isset($publicSongData['result']) || !is_array($publicSongData['result'])) {
            return []; // No tracks found, return empty array
        }

        return $publicSongData['result'];
    }

    /**
     * Process a single track from the Suno API.
     *
     * @param array $trackData
     * @return Track|null
     * @throws Exception
     */
    protected function processTrack(array $trackData): ?Track
    {
        // Extract Suno ID
        $sunoId = $trackData['id'] ?? null;
        
        // Skip if song with this Suno ID already exists
        if ($sunoId) {
            $existingTrack = Track::where('suno_id', $sunoId)->first();
                
            if ($existingTrack) {
                $this->comment("Track with Suno ID {$sunoId} already exists, skipping");
                return null;
            }
        }
    
        // Extract track info
        $title = $trackData['title'] ?? null;
        if (empty($title)) {
            $title = 'Untitled ' . Str::random(8);
        }

        $mp3Url = $trackData['audio_url'] ?? null;
        $imageUrl = $trackData['image_url'] ?? null;
        $tagsString = isset($trackData['metadata']['tags']) ? $trackData['metadata']['tags'] : '';
        
        // Extract metadata for genres
        $genreData = [];
        if (isset($trackData['metadata']['genres']) && is_array($trackData['metadata']['genres'])) {
            $genreData = $trackData['metadata']['genres'];
        }

        if (empty($mp3Url)) {
            throw new Exception("MP3 URL is missing for track: {$title}");
        }

        if (empty($imageUrl)) {
            throw new Exception("Image URL is missing for track: {$title}");
        }

        // Create the track
        $track = Track::create([
            'title' => $title,
            'mp3_url' => $mp3Url,
            'image_url' => $imageUrl,
            'genres_string' => $tagsString,
            'suno_id' => $sunoId,
            'status' => 'pending',
            'progress' => 0,
        ]);

        $this->info("Track created with ID: {$track->id} - {$title}");

        // Process genres
        if (!empty($tagsString)) {
            $this->processGenres($track, $tagsString, $genreData);
        }

        return $track;
    }

    /**
     * Display track preview for dry run.
     *
     * @param array $trackData
     * @param int $index
     * @return void
     */
    protected function displayTrackPreview(array $trackData, int $index): void
    {
        $title = $trackData['title'] ?? 'Untitled';
        $mp3Url = $trackData['audio_url'] ?? 'N/A';
        $imageUrl = $trackData['image_url'] ?? 'N/A';
        $tags = isset($trackData['metadata']['tags']) ? $trackData['metadata']['tags'] : 'N/A';
        $sunoId = $trackData['id'] ?? 'N/A';
        $playCount = $trackData['play_count'] ?? 0;
        $upvoteCount = $trackData['upvote_count'] ?? 0;

        $this->newLine();
        $this->info("Track #{$index}:");
        $this->line("  Suno ID: {$sunoId}");
        $this->line("  Title: {$title}");
        $this->line("  MP3 URL: {$mp3Url}");
        $this->line("  Image URL: {$imageUrl}");
        $this->line("  Tags: {$tags}");
        $this->line("  Play Count: {$playCount}");
        $this->line("  Upvotes: {$upvoteCount}");
    }

    /**
     * Process genres for a track.
     *
     * @param Track $track
     * @param string $genresString
     * @param array $genreData
     * @return void
     */
    protected function processGenres(Track $track, string $genresString, array $genreData = []): void
    {
        // Split genres by comma and clean them
        $genreNames = array_map('trim', explode(',', $genresString));
        $genreIds = [];

        foreach ($genreNames as $genreName) {
            if (empty($genreName)) {
                continue;
            }

            // Normalize genre name and create slug
            $normalizedName = ucwords(strtolower($genreName));
            $slug = Str::slug($normalizedName);

            // Create or find genre by slug (which is unique)
            $genre = Genre::firstOrCreate(
                ['slug' => $slug],
                ['name' => $normalizedName]
            );

            $genreIds[] = $genre->id;
        }

        // Attach genres to track
        if (!empty($genreIds)) {
            $track->genres()->sync($genreIds);
            $this->comment("Attached " . count($genreIds) . " genres to track");
        }
    }
} 