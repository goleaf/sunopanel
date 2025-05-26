<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\Track;
use App\Models\Genre;
use App\Jobs\ProcessTrack;

final class ImportFromJson extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'import:json 
                            {source : JSON source (URL or local file path)}
                            {--format=pipe : Data format (pipe, json, array)}
                            {--field=data : JSON field containing the track data}
                            {--dry-run : Preview import without creating tracks}
                            {--limit=0 : Limit number of tracks to import (0 = no limit)}
                            {--skip=0 : Skip first N tracks}
                            {--process : Automatically start processing imported tracks}
                            {--session-id= : Session ID for progress tracking}';

    /**
     * The console command description.
     */
    protected $description = 'Import music tracks from JSON data (URL or local file)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $source = $this->argument('source');
        $format = $this->option('format');
        $field = $this->option('field');
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $skip = (int) $this->option('skip');
        $autoProcess = $this->option('process');
        $sessionId = $this->option('session-id');

        $this->info("Starting JSON import from: {$source}");
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No tracks will be created');
        }

        // Update progress if session ID provided
        if ($sessionId) {
            $this->updateProgress($sessionId, 10, 'Loading JSON data...');
        }

        try {
            // Load JSON data
            $jsonData = $this->loadJsonData($source);
            
            if (!$jsonData) {
                if ($sessionId) {
                    $this->updateProgress($sessionId, 100, 'Failed to load JSON data', 'failed');
                }
                $this->error('Failed to load JSON data');
                return 1;
            }

            if ($sessionId) {
                $this->updateProgress($sessionId, 20, 'Extracting track data...');
            }

            // Extract track data from JSON
            $trackData = $this->extractTrackData($jsonData, $field);
            
            if (empty($trackData)) {
                if ($sessionId) {
                    $this->updateProgress($sessionId, 100, 'No track data found in JSON', 'failed');
                }
                $this->error('No track data found in JSON');
                return 1;
            }

            $this->info('Found ' . count($trackData) . ' tracks in JSON data');

            // Apply skip and limit
            if ($skip > 0) {
                $trackData = array_slice($trackData, $skip);
                $this->info("Skipped first {$skip} tracks");
            }

            if ($limit > 0 && count($trackData) > $limit) {
                $trackData = array_slice($trackData, 0, $limit);
                $this->info("Limited to {$limit} tracks");
            }

            if ($sessionId) {
                $this->updateProgress($sessionId, 30, 'Starting track import...', 'running', 0, 0, count($trackData));
            }

            // Parse and import tracks
            $imported = $this->importTracks($trackData, $format, $dryRun, $autoProcess, $sessionId);

            if ($sessionId) {
                $this->updateProgress($sessionId, 100, 
                    $dryRun ? "DRY RUN: Would import {$imported} tracks" : "Successfully imported {$imported} tracks", 
                    'completed', $imported, 0, count($trackData));
            }

            if ($dryRun) {
                $this->info("DRY RUN: Would import {$imported} tracks");
            } else {
                $this->info("Successfully imported {$imported} tracks");
            }

            return 0;

        } catch (\Exception $e) {
            if ($sessionId) {
                $this->updateProgress($sessionId, 100, 'Import failed: ' . $e->getMessage(), 'failed');
            }
            
            $this->error('Import failed: ' . $e->getMessage());
            Log::error('JSON import failed', [
                'source' => $source,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return 1;
        }
    }

    /**
     * Load JSON data from URL or local file.
     */
    private function loadJsonData(string $source): ?array
    {
        $this->info('Loading JSON data...');

        try {
            if (filter_var($source, FILTER_VALIDATE_URL)) {
                // Load from URL
                $this->info('Fetching data from URL...');
                $response = Http::timeout(30)->get($source);
                
                if (!$response->successful()) {
                    $this->error("Failed to fetch URL: HTTP {$response->status()}");
                    return null;
                }
                
                return $response->json();
                
            } else {
                // Load from local file
                $this->info('Reading local file...');
                
                if (!file_exists($source)) {
                    $this->error("File not found: {$source}");
                    return null;
                }
                
                $content = file_get_contents($source);
                if ($content === false) {
                    $this->error("Failed to read file: {$source}");
                    return null;
                }
                
                return json_decode($content, true);
            }

        } catch (\Exception $e) {
            $this->error('Failed to load JSON data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract track data from JSON structure.
     */
    private function extractTrackData(array $jsonData, string $field): array
    {
        // If field is specified, try to extract from that field
        if ($field !== 'data' && isset($jsonData[$field])) {
            $data = $jsonData[$field];
        } else {
            // Try common field names
            $commonFields = ['data', 'tracks', 'items', 'results', 'music'];
            $data = null;
            
            foreach ($commonFields as $commonField) {
                if (isset($jsonData[$commonField])) {
                    $data = $jsonData[$commonField];
                    $this->info("Found track data in field: {$commonField}");
                    break;
                }
            }
            
            // If no field found, assume the root is the data
            if ($data === null) {
                $data = $jsonData;
            }
        }

        // Ensure we have an array
        if (!is_array($data)) {
            return [];
        }

        // Check if this is an array of strings (pipe format) or objects
        if (isset($data[0])) {
            // If first element is a string, this is likely an array of pipe-delimited strings
            if (is_string($data[0])) {
                return $data;
            }
            // If first element is an array/object, return as is
            if (is_array($data[0])) {
                return $data;
            }
        }

        // If it's a single track object, wrap in array
        return [$data];
    }

    /**
     * Import tracks from parsed data.
     */
    private function importTracks(array $trackData, string $format, bool $dryRun, bool $autoProcess, ?string $sessionId = null): int
    {
        $imported = 0;
        $failed = 0;
        $total = count($trackData);
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        foreach ($trackData as $index => $data) {
            try {
                $trackInfo = $this->parseTrackData($data, $format);
                
                if (!$trackInfo) {
                    $this->newLine();
                    $this->warn("Skipping invalid track at index {$index}");
                    $failed++;
                    $progressBar->advance();
                    
                    // Update progress
                    if ($sessionId) {
                        $progress = 30 + (($index + 1) / $total) * 70;
                        $this->updateProgress($sessionId, (int)$progress, "Processing track " . ($index + 1) . "/{$total}...", 'running', $imported, $failed, $total);
                    }
                    continue;
                }

                if ($dryRun) {
                    $this->displayTrackPreview($trackInfo, $index);
                } else {
                    $track = $this->createTrack($trackInfo);
                    
                    if ($track && $autoProcess) {
                        ProcessTrack::dispatch($track);
                    }
                }

                $imported++;
                $progressBar->advance();

                // Update progress
                if ($sessionId) {
                    $progress = 30 + (($index + 1) / $total) * 70;
                    $this->updateProgress($sessionId, (int)$progress, "Processing track " . ($index + 1) . "/{$total}...", 'running', $imported, $failed, $total);
                }

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed to import track at index {$index}: " . $e->getMessage());
                $failed++;
                $progressBar->advance();
                
                // Update progress
                if ($sessionId) {
                    $progress = 30 + (($index + 1) / $total) * 70;
                    $this->updateProgress($sessionId, (int)$progress, "Processing track " . ($index + 1) . "/{$total}...", 'running', $imported, $failed, $total);
                }
            }
        }

        $progressBar->finish();
        $this->newLine();

        return $imported;
    }

    /**
     * Parse track data based on format.
     */
    private function parseTrackData($data, string $format): ?array
    {
        switch ($format) {
            case 'pipe':
                return $this->parsePipeFormat($data);
            case 'json':
                return $this->parseJsonFormat($data);
            case 'array':
                return $this->parseArrayFormat($data);
            default:
                // Auto-detect format
                if (is_string($data) && str_contains($data, '|')) {
                    return $this->parsePipeFormat($data);
                } elseif (is_array($data)) {
                    return $this->parseArrayFormat($data);
                } else {
                    return $this->parseJsonFormat($data);
                }
        }
    }

    /**
     * Parse pipe-delimited format: title|mp3_url|image_url|genres
     */
    private function parsePipeFormat($data): ?array
    {
        if (!is_string($data)) {
            return null;
        }

        $parts = explode('|', $data);
        
        if (count($parts) < 2) {
            return null;
        }

        return [
            'title' => trim($parts[0]),
            'mp3_url' => trim($parts[1]),
            'image_url' => isset($parts[2]) ? trim($parts[2]) : null,
            'genres' => isset($parts[3]) ? trim($parts[3]) : null,
        ];
    }

    /**
     * Parse JSON object format.
     */
    private function parseJsonFormat($data): ?array
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (!is_array($data)) {
            return null;
        }

        return $this->parseArrayFormat($data);
    }

    /**
     * Parse array format.
     */
    private function parseArrayFormat(array $data): ?array
    {
        // Try different field name variations
        $titleFields = ['title', 'name', 'track_name', 'song_name'];
        $mp3Fields = ['mp3_url', 'audio_url', 'url', 'mp3', 'audio', 'file_url'];
        $imageFields = ['image_url', 'artwork_url', 'cover_url', 'thumbnail_url', 'image', 'artwork'];
        $genreFields = ['genres', 'genre', 'tags', 'categories', 'style'];

        $title = $this->findFieldValue($data, $titleFields);
        $mp3Url = $this->findFieldValue($data, $mp3Fields);
        $imageUrl = $this->findFieldValue($data, $imageFields);
        $genres = $this->findFieldValue($data, $genreFields);

        if (!$title || !$mp3Url) {
            return null;
        }

        return [
            'title' => $title,
            'mp3_url' => $mp3Url,
            'image_url' => $imageUrl,
            'genres' => $genres,
        ];
    }

    /**
     * Find field value from array using multiple possible field names.
     */
    private function findFieldValue(array $data, array $fieldNames): ?string
    {
        foreach ($fieldNames as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                return is_array($data[$field]) ? implode(',', $data[$field]) : (string) $data[$field];
            }
        }

        return null;
    }

    /**
     * Display track preview for dry run.
     */
    private function displayTrackPreview(array $trackInfo, int $index): void
    {
        $this->newLine();
        $this->info("Track #{$index}:");
        $this->line("  Title: {$trackInfo['title']}");
        $this->line("  MP3 URL: {$trackInfo['mp3_url']}");
        
        if ($trackInfo['image_url']) {
            $this->line("  Image URL: {$trackInfo['image_url']}");
        }
        
        if ($trackInfo['genres']) {
            $this->line("  Genres: {$trackInfo['genres']}");
        }
    }

    /**
     * Create track in database.
     */
    private function createTrack(array $trackInfo): ?Track
    {
        try {
            // Check if track already exists
            $existingTrack = Track::where('title', $trackInfo['title'])
                ->where('mp3_url', $trackInfo['mp3_url'])
                ->first();

            if ($existingTrack) {
                $this->warn("Track already exists: {$trackInfo['title']}");
                return $existingTrack;
            }

            // Create track
            $track = Track::create([
                'title' => $trackInfo['title'],
                'mp3_url' => $trackInfo['mp3_url'],
                'image_url' => $trackInfo['image_url'],
                'status' => 'pending',
                'progress' => 0,
            ]);

            // Handle genres
            if ($trackInfo['genres']) {
                $this->attachGenres($track, $trackInfo['genres']);
            }

            Log::info('Track imported from JSON', [
                'track_id' => $track->id,
                'title' => $track->title,
                'mp3_url' => $track->mp3_url,
            ]);

            return $track;

        } catch (\Exception $e) {
            $this->error("Failed to create track: {$trackInfo['title']} - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Attach genres to track.
     */
    private function attachGenres(Track $track, string $genresString): void
    {
        $genreNames = array_map('trim', explode(',', $genresString));
        $genreIds = [];

        foreach ($genreNames as $genreName) {
            if (empty($genreName)) {
                continue;
            }

            // Find or create genre
            $genre = Genre::firstOrCreate(
                ['name' => $genreName],
                ['slug' => \Str::slug($genreName)]
            );

            $genreIds[] = $genre->id;
        }

        if (!empty($genreIds)) {
            $track->genres()->sync($genreIds);
        }
    }

    /**
     * Update progress for session-based tracking.
     */
    private function updateProgress(string $sessionId, int $progress, string $message, string $status = 'running', int $imported = 0, int $failed = 0, int $total = 0): void
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
