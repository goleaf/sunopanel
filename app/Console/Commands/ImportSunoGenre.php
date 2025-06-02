<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Genre;
use App\Models\Track;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ImportSunoGenre extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:suno-genre 
                            {--genre= : Genre term to search for (e.g., "Spanish Pop")}
                            {--from-index=0 : Starting index for pagination}
                            {--size=20 : Number of tracks per request}
                            {--pages=1 : Number of pages to fetch}
                            {--rank-by=most_relevant : Ranking method (most_relevant, trending, most_recent, etc.)}
                            {--process : Automatically start processing imported tracks}
                            {--dry-run : Preview import without creating tracks}
                            {--session-id= : Session ID for progress tracking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import tracks from Suno by genre using tag_song search';

    /**
     * Suno API configuration from user's curl request
     */
    private const API_BASE_URL = 'https://studio-api.prod.suno.com/api/search/';
    private const BEARER_TOKEN = 'eyJhbGciOiJSUzI1NiIsImNhdCI6ImNsX0I3ZDRQRDExMUFBQSIsImtpZCI6Imluc18yT1o2eU1EZzhscWRKRWloMXJvemY4T3ptZG4iLCJ0eXAiOiJKV1QifQ.eyJhdWQiOiJzdW5vLWFwaSIsImF6cCI6Imh0dHBzOi8vc3Vuby5jb20iLCJleHAiOjE3NDg4NTk1MzAsImZ2YSI6WzgsLTFdLCJodHRwczovL3N1bm8uYWkvY2xhaW1zL2NsZXJrX2lkIjoidXNlcl8yalRZbUxScVYzSDA3VDFCeDdFb3IxcWpNV20iLCJodHRwczovL3N1bm8uYWkvY2xhaW1zL2VtYWlsIjoiZ29sZWFmQGdtYWlsLmNvbSIsImh0dHBzOi8vc3Vuby5haS9jbGFpbXMvcGhvbmUiOm51bGwsImlhdCI6MTc0ODg1OTQ3MCwiaXNzIjoiaHR0cHM6Ly9jbGVyay5zdW5vLmNvbSIsImp0aSI6IjRlOGJmZTFkMjViMTNhNThmODUwIiwibmJmIjoxNzQ4ODU5NDYwLCJzaWQiOiJzZXNzXzJ4d3BQZnJkN1Y2ZlI1dlliQWZvbm02VTlxaiIsInN1YiI6InVzZXJfMmpUWW1MUnFWM0gwN1QxQng3RW9yMXFqTVdtIn0.gosPI06mXTF-W_IwTISbKnd3gcCnWZHoU3siZQuQuB5xy2V5SzT9JnF7LfHD_aYOe52mWzTKTlI5_vyW2m2InlZlyFAspY76__fZw-1AxGoSOLFoBjZ__AQdCF9yzatLs4Qdki4Bgy6eQQ_pegbGXYvEkEKniqprCaTVXlsv6OQcO0A8AItY1CA3XMFkCd1tRwJV8sbmJ0B_zpPvvLe_wJKevdvfE8-9yychrMGb-xh-ca3PqJ13w1ovJoblIdr4yiziCulghYO7Kp_WO5mO81-tt9Iob2WooW_Q6t-_CFHiw8qof6pYBZ3pgga8WGdnv76BfwMubvABlNtT8kC95Q';
    private const BROWSER_TOKEN = '{"token":"eyJ0aW1lc3RhbXAiOjE3NDg4NTk0NzU0OTV9"}';
    private const DEVICE_ID = '42c6837d-7c0f-4093-b9bc-10d1671749aa';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $genre = $this->option('genre');
        $fromIndex = (int) $this->option('from-index');
        $size = (int) $this->option('size');
        $pages = (int) $this->option('pages');
        $rankBy = $this->option('rank-by');
        $autoProcess = $this->option('process');
        $dryRun = $this->option('dry-run');
        $sessionId = $this->option('session-id');

        if (empty($genre)) {
            $this->error('Genre parameter is required. Use --genre="Spanish Pop" for example.');
            return 1;
        }

        $this->info("Fetching tracks from Suno genre search API");
        $this->info("Genre: {$genre}");
        $this->info("Size per page: {$size}");
        $this->info("Pages to fetch: {$pages}");
        $this->info("Rank by: {$rankBy}");
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No tracks will be created');
        }

        if ($sessionId) {
            $this->updateProgress($sessionId, 10, 'Starting Suno Genre import...');
        }

        $totalImported = 0;
        $totalFailed = 0;
        $totalSkipped = 0;

        try {
            for ($page = 1; $page <= $pages; $page++) {
                $this->info("\nFetching page {$page}/{$pages}...");
                
                if ($sessionId) {
                    $pageProgress = 10 + (($page - 1) / $pages) * 80;
                    $this->updateProgress($sessionId, (int)$pageProgress, "Fetching page {$page}/{$pages}...", 'running', $totalImported, $totalFailed);
                }
                
                $currentFromIndex = $fromIndex + (($page - 1) * $size);
                
                $tracks = $this->fetchTracksFromAPI($genre, $currentFromIndex, $size, $rankBy);
                
                if (empty($tracks)) {
                    $this->warn("No tracks found on page {$page}");
                    continue;
                }

                $this->info("Found " . count($tracks) . " tracks on page {$page}");

                foreach ($tracks as $index => $trackData) {
                    try {
                        if ($dryRun) {
                            $this->displayTrackPreview($trackData, $index + 1);
                            $totalImported++;
                        } else {
                            // Check for duplicates before creating
                            $sunoId = $trackData['id'] ?? null;
                            if ($sunoId && Track::where('suno_id', $sunoId)->exists()) {
                                $this->comment("Track with Suno ID {$sunoId} already exists, skipping");
                                $totalSkipped++;
                                continue;
                            }

                            $track = $this->processTrack($trackData);
                            if ($track) {
                                $totalImported++;
                                
                                if ($autoProcess) {
                                    \App\Jobs\ProcessTrack::dispatch($track);
                                    $this->comment("Queued track for processing: {$track->title}");
                                }
                            } else {
                                $totalSkipped++;
                            }
                        }
                    } catch (Exception $e) {
                        $totalFailed++;
                        $this->error("Failed to process track: " . $e->getMessage());
                        Log::error("Failed to process track from genre search", [
                            'track_data' => $trackData,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            if ($sessionId) {
                $message = $dryRun 
                    ? "DRY RUN: Would import {$totalImported} tracks, {$totalSkipped} skipped, {$totalFailed} failed"
                    : "Successfully imported {$totalImported} tracks, {$totalSkipped} skipped, {$totalFailed} failed";
                    
                $this->updateProgress($sessionId, 100, $message, 'completed', $totalImported, $totalFailed);
            }

            $this->newLine();
            if ($dryRun) {
                $this->info("DRY RUN: Would import {$totalImported} tracks");
            } else {
                $this->info("Successfully imported {$totalImported} tracks");
            }
            $this->info("Skipped: {$totalSkipped} tracks");
            $this->info("Failed: {$totalFailed} tracks");

            return 0;

        } catch (Exception $e) {
            if ($sessionId) {
                $this->updateProgress($sessionId, 100, 'Import failed: ' . $e->getMessage(), 'failed', $totalImported, $totalFailed);
            }
            
            $this->error("Failed to import from Suno genre search: " . $e->getMessage());
            Log::error("Failed to import from Suno genre search", [
                'genre' => $genre,
                'error' => $e->getMessage(),
            ]);
            return 1;
        }
    }

    /**
     * Fetch tracks from Suno search API using tag_song search type.
     *
     * @param string $genre
     * @param int $fromIndex
     * @param int $size
     * @param string $rankBy
     * @return array
     * @throws Exception
     */
    protected function fetchTracksFromAPI(string $genre, int $fromIndex, int $size, string $rankBy): array
    {
        // URL encode the genre term
        $encodedGenre = urlencode($genre);
        
        $payload = [
            'search_queries' => [
                [
                    'name' => 'tag_song',
                    'search_type' => 'tag_song',
                    'term' => $encodedGenre,
                    'from_index' => $fromIndex,
                    'rank_by' => $rankBy,
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
            'dnt' => '1',
            'origin' => 'https://suno.com',
            'priority' => 'u=1, i',
            'referer' => 'https://suno.com/',
            'sec-ch-ua' => '"Chromium";v="136", "Google Chrome";v="136", "Not.A/Brand";v="99"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-site',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
        ])->timeout(60)->post(self::API_BASE_URL, $payload);

        if (!$response->successful()) {
            $this->error("API Response Status: {$response->status()}");
            $this->error("API Response Body: " . $response->body());
            throw new Exception("Failed to fetch data from Suno genre search API. Status: {$response->status()}");
        }

        $data = $response->json();
        
        // Extract tracks from the search results structure
        if (!isset($data['result']) || !is_array($data['result'])) {
            throw new Exception("No result found in Suno genre search API response");
        }
        
        if (!isset($data['result']['tag_song']) || !is_array($data['result']['tag_song'])) {
            throw new Exception("No tag_song data found in search results");
        }
        
        $tagSongData = $data['result']['tag_song'];
        
        if (!isset($tagSongData['result']) || !is_array($tagSongData['result'])) {
            return []; // No tracks found, return empty array
        }

        return $tagSongData['result'];
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

    /**
     * Update progress for session-based tracking.
     */
    protected function updateProgress(string $sessionId, int $progress, string $message, string $status = 'running', int $imported = 0, int $failed = 0): void
    {
        $progressData = [
            'status' => $status,
            'progress' => $progress,
            'message' => $message,
            'imported' => $imported,
            'failed' => $failed,
            'updated_at' => now()->toISOString(),
        ];

        Cache::put("import_progress_{$sessionId}", $progressData, 3600); // 1 hour
    }
} 