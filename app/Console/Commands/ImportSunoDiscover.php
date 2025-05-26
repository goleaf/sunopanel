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

class ImportSunoDiscover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:suno-discover 
                            {--section=trending_songs : Section name to fetch (trending_songs, new_songs, etc.)}
                            {--page-size=50 : Number of tracks per page}
                            {--pages=1 : Number of pages to fetch}
                            {--start-index=0 : Starting index for pagination}
                            {--process : Automatically start processing imported tracks}
                            {--dry-run : Preview import without creating tracks}
                            {--session-id= : Session ID for progress tracking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import tracks from Suno discover API (trending, new songs, etc.)';

    /**
     * Suno API configuration
     */
    private const API_BASE_URL = 'https://studio-api.prod.suno.com/api/discover/';
    private const BEARER_TOKEN = 'eyJhbGciOiJSUzI1NiIsImNhdCI6ImNsX0I3ZDRQRDExMUFBQSIsImtpZCI6Imluc18yT1o2eU1EZzhscWRKRWloMXJvemY4T3ptZG4iLCJ0eXAiOiJKV1QifQ.eyJhdWQiOiJzdW5vLWFwaSIsImF6cCI6Imh0dHBzOi8vc3Vuby5jb20iLCJleHAiOjE3NDgyMTk1NDQsImZ2YSI6WzUxMTQzLC0xXSwiaHR0cHM6Ly9zdW5vLmFpL2NsYWltcy9jbGVya19pZCI6InVzZXJfMmpUWW1MUnFWM0gwN1QxQng3RW9yMXFqTVdtIiwiaHR0cHM6Ly9zdW5vLmFpL2NsYWltcy9lbWFpbCI6ImdvbGVhZkBnbWFpbC5jb20iLCJodHRwczovL3N1bm8uYWkvY2xhaW1zL3Bob25lIjpudWxsLCJpYXQiOjE3NDgyMTk0ODQsImlzcyI6Imh0dHBzOi8vY2xlcmsuc3Vuby5jb20iLCJqdGkiOiI1NjA5MjNhY2MxYjM0NTRkN2MzMSIsIm5iZiI6MTc0ODIxOTQ3NCwic2lkIjoic2Vzc18ydnpiWElYaUdNV1c1RjJNU0FNaXlLRzFtZ2kiLCJzdWIiOiJ1c2VyXzJqVFltTFJxVjNIMDdUMUJ4N0VvcjFxak1XbSJ9.HWe9SkP_g7vW1EfibwrJ-83GdJg0Bpb6WnYjbHI9xNm7iG1GsHWEoosPxxbuoFDsm96mCpVCWHo7HfqAYQgxbLRfvLTiVrTeSVcGTURxaWmCJ0MQz7DviGzpZwf2c7XhbEKWq5NXP-0EthNO_zBWh61A-MKfrgyhvmlTsaDbJJP2E4MezDx-864NBeo36QrqxmWnZnALRBl89Y3Xf9l41i4_Ulv_4fl0Ttu7aupVh2dl22VCytYtfAUwwEjYp-u73IlVoBSbMRPY6LppZENPDJYyhYjNf-14WwBCPWCInI9J5REkSQm0lNJMnUmQU2m-bqnBKmv2z5v7eZrQ69tTFw';
    private const BROWSER_TOKEN = '{"token":"eyJ0aW1lc3RhbXAiOjE3NDgyMTk0ODUwMzZ9"}';
    private const DEVICE_ID = '25b238d9-fc72-454e-bf39-31b22888b1df';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $section = $this->option('section');
        $pageSize = (int) $this->option('page-size');
        $pages = (int) $this->option('pages');
        $startIndex = (int) $this->option('start-index');
        $autoProcess = $this->option('process');
        $dryRun = $this->option('dry-run');
        $sessionId = $this->option('session-id');

        $this->info("Fetching tracks from Suno discover API");
        $this->info("Section: {$section}");
        $this->info("Page size: {$pageSize}");
        $this->info("Pages to fetch: {$pages}");
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No tracks will be created');
        }

        if ($sessionId) {
            $this->updateProgress($sessionId, 10, 'Starting Suno Discover import...');
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
                
                $currentStartIndex = $startIndex + (($page - 1) * $pageSize);
                
                $tracks = $this->fetchTracksFromAPI($section, $pageSize, $currentStartIndex);
                
                if (empty($tracks)) {
                    $this->warning("No tracks found on page {$page}");
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
                        Log::error("Failed to process Suno discover track", [
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
            
            $this->error("Failed to import from Suno discover: " . $e->getMessage());
            Log::error("Failed to import from Suno discover", [
                'section' => $section,
                'error' => $e->getMessage(),
            ]);
            return 1;
        }
    }

    /**
     * Fetch tracks from Suno discover API.
     *
     * @param string $section
     * @param int $pageSize
     * @param int $startIndex
     * @return array
     * @throws Exception
     */
    protected function fetchTracksFromAPI(string $section, int $pageSize, int $startIndex): array
    {
        $payload = [
            'start_index' => $startIndex,
            'page_size' => 1,
            'section_name' => $section,
            'section_content' => null,
            'secondary_section_content' => null,
            'page' => 1,
            'section_size' => $pageSize,
            'disable_shuffle' => true,
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
            'sec-ch-ua-platform' => '"macOS"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-site',
            'referer' => 'https://suno.com/',
        ])->timeout(60)->post(self::API_BASE_URL, $payload);

        if (!$response->successful()) {
            $this->error("API Response Status: {$response->status()}");
            $this->error("API Response Body: " . $response->body());
            throw new Exception("Failed to fetch data from Suno API. Status: {$response->status()}");
        }

        $data = $response->json();
        
        // Extract tracks from the sections structure
        if (!isset($data['sections']) || !is_array($data['sections']) || empty($data['sections'])) {
            throw new Exception("No sections found in Suno API response");
        }
        
        $section = $data['sections'][0];
        
        if (!isset($section['items']) || !is_array($section['items'])) {
            throw new Exception("No items found in section");
        }

        return $section['items'];
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

        $this->newLine();
        $this->info("Track #{$index}:");
        $this->line("  Suno ID: {$sunoId}");
        $this->line("  Title: {$title}");
        $this->line("  MP3 URL: {$mp3Url}");
        $this->line("  Image URL: {$imageUrl}");
        $this->line("  Tags: {$tags}");
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
    protected function updateProgress(string $sessionId, int $progress, string $message, string $status = 'running', int $imported = 0, int $failed = 0, int $total = 0): void
    {
        Cache::put("import_progress_{$sessionId}", [
            'status' => $status,
            'progress' => $progress,
            'message' => $message,
            'imported' => $imported,
            'failed' => $failed,
            'total' => $total,
        ], 3600);
    }
} 