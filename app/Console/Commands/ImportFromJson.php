<?php

namespace App\Console\Commands;

use App\Models\Genre;
use App\Models\Track;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportFromJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:json {source? : URL or file path to JSON data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import tracks from JSON data (URL or file) in format: title|mp3_url|image_url|genres';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $source = $this->argument('source');
        
        if (empty($source)) {
            $this->error('Please provide a JSON source (URL or file path)');
            return 1;
        }

        try {
            // Determine if source is URL or file path
            if (filter_var($source, FILTER_VALIDATE_URL)) {
                $this->info("Fetching JSON data from URL: {$source}");
                $jsonData = $this->fetchFromUrl($source);
            } else {
                $this->info("Reading JSON data from file: {$source}");
                $jsonData = $this->readFromFile($source);
            }

            if (empty($jsonData)) {
                $this->error('No data found in the source');
                return 1;
            }

            return $this->processJsonData($jsonData);
        } catch (Exception $e) {
            $this->error("Failed to import from JSON: " . $e->getMessage());
            Log::error("Failed to import from JSON", [
                'source' => $source,
                'error' => $e->getMessage(),
            ]);
            return 1;
        }
    }

    /**
     * Fetch JSON data from URL.
     *
     * @param string $url
     * @return array
     * @throws Exception
     */
    protected function fetchFromUrl(string $url): array
    {
        $response = Http::timeout(60)->get($url);
        
        if (!$response->successful()) {
            throw new Exception("Failed to fetch data from URL. Status: {$response->status()}");
        }

        $data = $response->json();
        
        if (!is_array($data)) {
            throw new Exception("Invalid JSON data format");
        }

        return $data;
    }

    /**
     * Read JSON data from file.
     *
     * @param string $filePath
     * @return array
     * @throws Exception
     */
    protected function readFromFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        
        if ($content === false) {
            throw new Exception("Failed to read file: {$filePath}");
        }

        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON format: " . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new Exception("JSON data must be an array");
        }

        return $data;
    }

    /**
     * Process JSON data and import tracks.
     *
     * @param array $jsonData
     * @return int
     */
    protected function processJsonData(array $jsonData): int
    {
        $totalTracks = count($jsonData);
        $this->info("Found {$totalTracks} tracks to process");

        $processedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($jsonData as $index => $trackData) {
            $this->info("\nProcessing track " . ($index + 1) . "/{$totalTracks}");
            
            try {
                // Parse track data based on format: title|mp3_url|image_url|genres
                if (is_string($trackData)) {
                    $parsed = $this->parseTrackString($trackData);
                } elseif (is_array($trackData)) {
                    $parsed = $this->parseTrackArray($trackData);
                } else {
                    throw new Exception("Invalid track data format");
                }

                if ($this->processTrack($parsed)) {
                    $processedCount++;
                    $this->info("Successfully processed: " . $parsed['title']);
                } else {
                    $skippedCount++;
                    $this->comment("Skipped (already exists): " . $parsed['title']);
                }
            } catch (Exception $e) {
                $failedCount++;
                $this->error("Failed to process track: " . $e->getMessage());
                Log::error("Failed to process JSON track", [
                    'track_data' => $trackData,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Import finished. Processed: {$processedCount}, Skipped: {$skippedCount}, Failed: {$failedCount}");
        
        return $failedCount > 0 ? 1 : 0;
    }

    /**
     * Parse track data from string format: title|mp3_url|image_url|genres
     *
     * @param string $trackString
     * @return array
     * @throws Exception
     */
    protected function parseTrackString(string $trackString): array
    {
        $parts = explode('|', $trackString);
        
        if (count($parts) < 3) {
            throw new Exception("Invalid track format. Expected: title|mp3_url|image_url|genres");
        }

        return [
            'title' => trim($parts[0]),
            'mp3_url' => trim($parts[1]),
            'image_url' => trim($parts[2]),
            'genres' => isset($parts[3]) ? trim($parts[3]) : '',
        ];
    }

    /**
     * Parse track data from array format.
     *
     * @param array $trackArray
     * @return array
     * @throws Exception
     */
    protected function parseTrackArray(array $trackArray): array
    {
        if (!isset($trackArray['title']) || !isset($trackArray['mp3_url']) || !isset($trackArray['image_url'])) {
            throw new Exception("Missing required fields: title, mp3_url, image_url");
        }

        return [
            'title' => $trackArray['title'],
            'mp3_url' => $trackArray['mp3_url'],
            'image_url' => $trackArray['image_url'],
            'genres' => $trackArray['genres'] ?? '',
        ];
    }

    /**
     * Process a single track.
     *
     * @param array $trackData
     * @return bool True if processed, false if skipped
     * @throws Exception
     */
    protected function processTrack(array $trackData): bool
    {
        $title = $trackData['title'];
        $mp3Url = $trackData['mp3_url'];
        $imageUrl = $trackData['image_url'];
        $genresString = $trackData['genres'];

        // Validate required fields
        if (empty($title)) {
            throw new Exception("Title is required");
        }

        if (empty($mp3Url)) {
            throw new Exception("MP3 URL is required");
        }

        if (empty($imageUrl)) {
            throw new Exception("Image URL is required");
        }

        // Check if track already exists by title and mp3_url
        $existingTrack = Track::where('title', $title)
            ->where('mp3_url', $mp3Url)
            ->first();

        if ($existingTrack) {
            return false; // Skip existing track
        }

        // Create the track
        $track = Track::create([
            'title' => $title,
            'mp3_url' => $mp3Url,
            'image_url' => $imageUrl,
            'genres_string' => $genresString,
            'status' => 'pending',
            'progress' => 0,
        ]);

        $this->comment("Track created with ID: {$track->id}");

        // Process genres if provided
        if (!empty($genresString)) {
            $this->processGenres($track, $genresString);
        }

        return true;
    }

    /**
     * Process genres for a track.
     *
     * @param Track $track
     * @param string $genresString
     * @return void
     */
    protected function processGenres(Track $track, string $genresString): void
    {
        // Split genres by comma and clean them
        $genreNames = array_map('trim', explode(',', $genresString));
        $genreIds = [];

        foreach ($genreNames as $genreName) {
            if (empty($genreName)) {
                continue;
            }

            // Create or find genre
            $genre = Genre::firstOrCreate(
                ['name' => $genreName],
                ['slug' => Str::slug($genreName)]
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
