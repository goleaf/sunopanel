<?php

namespace App\Console\Commands;

use App\Models\Track;
use App\Models\Genre;
use App\Jobs\ProcessTrack;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ParseAllGenres extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:all-genres 
                            {--limit=50 : Number of tracks to fetch per genre}
                            {--dry-run : Show what would be imported without actually importing}
                            {--process : Automatically start processing imported tracks}
                            {--check-downloads : Check download status of existing tracks}
                            {--genres=* : Specific genres to parse (leave empty for all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse all genres to get new songs and check download status';

    /**
     * Popular music genres to search for.
     */
    private array $popularGenres = [
        'city pop', 'synthwave', 'lo-fi', 'jazz', 'electronic', 'ambient', 
        'rock', 'pop', 'classical', 'hip hop', 'r&b', 'soul', 'funk', 
        'disco', 'house', 'techno', 'trance', 'dubstep', 'drum and bass',
        'reggae', 'ska', 'punk', 'metal', 'indie', 'alternative', 'grunge',
        'country', 'folk', 'blues', 'gospel', 'latin', 'bossa nova',
        'chillout', 'downtempo', 'trip hop', 'new age', 'world music',
        'experimental', 'minimal', 'progressive', 'psychedelic', 'shoegaze',
        'post rock', 'math rock', 'emo', 'hardcore', 'breakbeat',
        'garage', 'uk garage', 'grime', 'trap', 'drill', 'phonk'
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🎵 Starting comprehensive genre parsing and song collection...');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');
        $autoProcess = $this->option('process');
        $checkDownloads = $this->option('check-downloads');
        $specificGenres = $this->option('genres');

        // Use specific genres if provided, otherwise use all popular genres
        $genresToParse = !empty($specificGenres) ? $specificGenres : $this->popularGenres;

        $totalImported = 0;
        $totalSkipped = 0;
        $downloadIssues = [];

        // Check download status first if requested
        if ($checkDownloads) {
            $this->info('🔍 Checking download status of existing tracks...');
            $downloadIssues = $this->checkDownloadStatus();
            $this->newLine();
        }

        // Parse each genre
        foreach ($genresToParse as $genre) {
            $this->info("🎼 Processing genre: {$genre}");
            
            try {
                $result = $this->parseGenre($genre, $limit, $dryRun, $autoProcess);
                $totalImported += $result['imported'];
                $totalSkipped += $result['skipped'];
                
                $this->line("  ✅ Imported: {$result['imported']}, Skipped: {$result['skipped']}");
                
                // Small delay to avoid overwhelming the API
                sleep(1);
                
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to process genre {$genre}: " . $e->getMessage());
                Log::error("Genre parsing failed for {$genre}: " . $e->getMessage());
            }
        }

        // Summary
        $this->newLine();
        $this->info('📊 Summary:');
        $this->table(['Metric', 'Count'], [
            ['Genres Processed', count($genresToParse)],
            ['Total Tracks Imported', $totalImported],
            ['Total Tracks Skipped', $totalSkipped],
            ['Download Issues Found', count($downloadIssues)],
        ]);

        if (!empty($downloadIssues)) {
            $this->warn('⚠️ Download Issues Found:');
            foreach ($downloadIssues as $issue) {
                $this->line("  • {$issue}");
            }
        }

        if ($dryRun) {
            $this->comment('💡 This was a dry run. Use --process to actually import and process tracks.');
        } elseif ($totalImported > 0 && !$autoProcess) {
            $this->comment('💡 Use --process to automatically start processing imported tracks.');
        }

        return Command::SUCCESS;
    }

    /**
     * Parse a specific genre and import new tracks.
     */
    private function parseGenre(string $genre, int $limit, bool $dryRun, bool $autoProcess): array
    {
        $imported = 0;
        $skipped = 0;

        // Try multiple sources for each genre
        $sources = [
            'discover' => $this->getFromDiscover($genre, $limit),
            'search' => $this->getFromSearch($genre, $limit),
        ];

        foreach ($sources as $sourceName => $tracks) {
            if (empty($tracks)) {
                continue;
            }

            $this->line("    📡 Processing {$sourceName} source ({" . count($tracks) . "} tracks)");

            foreach ($tracks as $trackData) {
                try {
                    if ($this->shouldImportTrack($trackData)) {
                        if (!$dryRun) {
                            $track = $this->createTrack($trackData, $genre);
                            if ($track) {
                                $imported++;
                                
                                if ($autoProcess) {
                                    ProcessTrack::dispatch($track);
                                    $this->line("      ⚡ Queued for processing: {$track->title}");
                                } else {
                                    $this->line("      ✅ Imported: {$track->title}");
                                }
                            }
                        } else {
                            $imported++;
                            $this->line("      🔍 Would import: {$trackData['title']}");
                        }
                    } else {
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $this->error("      ❌ Failed to process track: " . $e->getMessage());
                    Log::error("Track processing failed: " . $e->getMessage(), $trackData);
                }
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * Get tracks from Suno discover API.
     */
    private function getFromDiscover(string $genre, int $limit): array
    {
        try {
            $response = Http::timeout(30)->get('https://studio-api.suno.ai/api/feed/', [
                'page' => 0,
                'page_size' => min($limit, 50),
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $tracks = [];

            if (isset($data['clips']) && is_array($data['clips'])) {
                foreach ($data['clips'] as $clip) {
                    if ($this->matchesGenre($clip, $genre)) {
                        $tracks[] = $this->formatTrackData($clip);
                    }
                }
            }

            return $tracks;
        } catch (\Exception $e) {
            Log::error("Discover API failed for genre {$genre}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get tracks from Suno search API.
     */
    private function getFromSearch(string $genre, int $limit): array
    {
        try {
            $response = Http::timeout(30)->get('https://studio-api.suno.ai/api/search/', [
                'query' => $genre,
                'page' => 0,
                'page_size' => min($limit, 50),
                'include_instrumental' => 'true',
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $tracks = [];

            if (isset($data['clips']) && is_array($data['clips'])) {
                foreach ($data['clips'] as $clip) {
                    $tracks[] = $this->formatTrackData($clip);
                }
            }

            return $tracks;
        } catch (\Exception $e) {
            Log::error("Search API failed for genre {$genre}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if a clip matches the target genre.
     */
    private function matchesGenre(array $clip, string $targetGenre): bool
    {
        $genreFields = ['tags', 'genre', 'style', 'metadata'];
        
        foreach ($genreFields as $field) {
            if (isset($clip[$field])) {
                $value = is_array($clip[$field]) ? implode(' ', $clip[$field]) : (string) $clip[$field];
                if (Str::contains(strtolower($value), strtolower($targetGenre))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Format track data from API response.
     */
    private function formatTrackData(array $clip): array
    {
        return [
            'id' => $clip['id'] ?? null,
            'title' => $clip['title'] ?? 'Unknown Title',
            'mp3_url' => $clip['audio_url'] ?? null,
            'image_url' => $clip['image_url'] ?? null,
            'genres' => $clip['tags'] ?? ($clip['genre'] ?? ''),
            'duration' => $clip['duration'] ?? null,
            'created_at' => $clip['created_at'] ?? null,
        ];
    }

    /**
     * Check if a track should be imported.
     */
    private function shouldImportTrack(array $trackData): bool
    {
        // Skip if missing essential data
        if (empty($trackData['title']) || empty($trackData['mp3_url']) || empty($trackData['image_url'])) {
            return false;
        }

        // Skip if not a Suno.ai URL
        if (!Str::contains($trackData['mp3_url'], 'suno.ai')) {
            return false;
        }

        // Skip if already exists
        $exists = Track::where('title', $trackData['title'])
            ->orWhere('mp3_url', $trackData['mp3_url'])
            ->exists();

        return !$exists;
    }

    /**
     * Create a track from API data.
     */
    private function createTrack(array $trackData, string $defaultGenre): ?Track
    {
        try {
            // Extract suno_id from URL
            $sunoId = null;
            if (preg_match('/https:\/\/cdn[0-9]?\.suno\.ai\/([a-f0-9-]{36})\./', $trackData['mp3_url'], $matches)) {
                $sunoId = $matches[1];
            }

            // Prepare genres string
            $genresString = '';
            if (!empty($trackData['genres'])) {
                $genresString = is_array($trackData['genres']) ? implode(', ', $trackData['genres']) : $trackData['genres'];
            }
            if (empty($genresString)) {
                $genresString = $defaultGenre;
            }

            $track = Track::create([
                'title' => $trackData['title'],
                'suno_id' => $sunoId,
                'mp3_url' => $trackData['mp3_url'],
                'image_url' => $trackData['image_url'],
                'genres_string' => $genresString,
                'status' => 'pending',
                'progress' => 0,
            ]);

            return $track;
        } catch (\Exception $e) {
            Log::error("Failed to create track: " . $e->getMessage(), $trackData);
            return null;
        }
    }

    /**
     * Check download status of existing tracks.
     */
    private function checkDownloadStatus(): array
    {
        $issues = [];

        $this->line('  📊 Checking track statuses...');
        
        // Check tracks by status
        $statusCounts = Track::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->table(['Status', 'Count'], collect($statusCounts)->map(function($count, $status) {
            return [$status, $count];
        })->toArray());

        // Check for failed tracks
        $failedTracks = Track::where('status', 'failed')->count();
        if ($failedTracks > 0) {
            $issues[] = "{$failedTracks} tracks have failed status";
        }

        // Check for stuck processing tracks
        $stuckTracks = Track::where('status', 'processing')
            ->where('updated_at', '<', now()->subHours(2))
            ->count();
        if ($stuckTracks > 0) {
            $issues[] = "{$stuckTracks} tracks stuck in processing for over 2 hours";
        }

        // Check for tracks without files
        $missingFiles = Track::where('status', 'completed')
            ->where(function($query) {
                $query->whereNull('mp3_path')
                      ->orWhereNull('image_path')
                      ->orWhereNull('mp4_path');
            })
            ->count();
        if ($missingFiles > 0) {
            $issues[] = "{$missingFiles} completed tracks missing file paths";
        }

        // Check queue status
        $queueJobs = \Illuminate\Support\Facades\DB::table('jobs')->count();
        $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
        
        $this->line('  🔄 Queue Status:');
        $this->table(['Queue', 'Count'], [
            ['Pending Jobs', $queueJobs],
            ['Failed Jobs', $failedJobs],
        ]);

        if ($queueJobs > 100) {
            $issues[] = "Large queue backlog: {$queueJobs} pending jobs";
        }

        if ($failedJobs > 0) {
            $issues[] = "{$failedJobs} failed jobs in queue";
        }

        return $issues;
    }
}
